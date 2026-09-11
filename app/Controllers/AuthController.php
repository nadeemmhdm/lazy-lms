<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Session;
use App\Helpers\AuthHelper;
use App\Helpers\TotpHelper;
use App\Database;

class AuthController extends BaseController {
    public function showLogin(Request $request): string {
        if (AuthHelper::check()) {
            $this->redirectRoleHome();
        }

        $settings = $this->getSettings();

        return Response::view('auth/login', [
            'title' => ($settings['login_title'] ?? 'Sign In') . ' - ' . ($settings['app_name'] ?? 'Open LMS'),
            'settings' => $settings,
        ], null);
    }

    public function login(Request $request): never {
        $ip = $request->ip();
        $email = strtolower(trim((string)$request->input('email')));
        $password = (string)$request->input('password');

        // 1. Rate limiting & Brute Force Lockout check
        $lockout = AuthHelper::checkLoginLockout($ip, $email);
        if ($lockout['locked']) {
            Session::flash('error', $lockout['message']);
            redirect('/login');
        }

        if (empty($email) || empty($password)) {
            Session::flash('error', 'Please enter both your email/username and password.');
            redirect('/login');
        }

        // 2. Fetch user record
        $user = Database::fetchOne(
            "SELECT u.*, r.slug as role_slug 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE LOWER(u.email) = ? 
             LIMIT 1",
            [$email]
        );

        // Never reveal if email exists: use generic failure
        $genericError = 'Invalid email address or password.';

        if (!$user || !AuthHelper::verifyPassword($password, $user['password'])) {
            AuthHelper::recordFailedLogin($ip, $email);
            Session::flash('error', $genericError);
            redirect('/login');
        }

        // 3. Status check
        if ($user['status'] !== 'active') {
            AuthHelper::recordFailedLogin($ip, $email);
            Session::flash('error', 'Your account is inactive, archived, or suspended. Please contact your administrator.');
            redirect('/login');
        }

        // 4. Check TOTP 2FA
        $twoFactor = Database::fetchOne(
            "SELECT * FROM two_factor_auth WHERE user_id = ? AND enabled = 1 LIMIT 1",
            [$user['id']]
        );

        if ($twoFactor) {
            // Require 2FA challenge
            Session::set('2fa_pending_user_id', (int)$user['id']);
            Session::set('2fa_pending_remember', !empty($request->input('remember')) ? 1 : 0);
            Session::set('2fa_pending_expires', time() + 300); // 5 minutes
            redirect('/login/2fa');
        }

        // 5. Complete Login
        AuthHelper::login($user);
        if (!empty($request->input('remember'))) {
            AuthHelper::setRememberToken((int)$user['id']);
        }
        AuthHelper::checkAndRecordDevice((int)$user['id'], $ip, $request->userAgent());
        AuthHelper::recordSecurityEvent((int)$user['id'], 'security.login_success', ['email' => $user['email']]);
        $this->audit('user.login', 'user', $user['id'], ['email' => $user['email']]);

        // If force password change is active
        if ((int)$user['force_password_change'] === 1) {
            redirect('/profile/force-password-change');
        }

        $this->redirectRoleHome();
    }

    public function show2fa(Request $request): string {
        $userId = Session::get('2fa_pending_user_id');
        $expires = Session::get('2fa_pending_expires', 0);

        if (!$userId || time() > $expires) {
            Session::remove('2fa_pending_user_id');
            redirect('/login');
        }

        return Response::view('auth/2fa', [
            'title' => 'Two-Factor Authentication Verification',
        ], null);
    }

    public function verify2fa(Request $request): never {
        $userId = Session::get('2fa_pending_user_id');
        $expires = Session::get('2fa_pending_expires', 0);

        if (!$userId || time() > $expires) {
            Session::flash('error', 'Verification session expired. Please log in again.');
            redirect('/login');
        }

        $code = trim((string)$request->input('code'));
        $twoFactor = Database::fetchOne(
            "SELECT * FROM two_factor_auth WHERE user_id = ? AND enabled = 1 LIMIT 1",
            [$userId]
        );

        if (!$twoFactor) {
            Session::remove('2fa_pending_user_id');
            redirect('/login');
        }

        $isValid = TotpHelper::verify($twoFactor['secret'], $code);
        $usedRecoveryCode = false;

        // Check recovery codes if TOTP fails
        if (!$isValid && !empty($code)) {
            $hashedCodes = json_decode($twoFactor['recovery_codes'] ?? '[]', true) ?: [];
            if (TotpHelper::verifyAndConsumeRecoveryCode($code, $hashedCodes)) {
                $isValid = true;
                $usedRecoveryCode = true;
                // Update remaining recovery codes
                Database::query(
                    "UPDATE two_factor_auth SET recovery_codes = ? WHERE id = ?",
                    [json_encode($hashedCodes), $twoFactor['id']]
                );
            }
        }

        if (!$isValid) {
            Session::flash('error', 'Invalid 2FA code or recovery code.');
            redirect('/login/2fa');
        }

        // Login user
        $user = Database::fetchOne(
            "SELECT u.*, r.slug as role_slug 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE u.id = ? LIMIT 1",
            [$userId]
        );

        $remember = Session::get('2fa_pending_remember');
        Session::remove('2fa_pending_user_id');
        Session::remove('2fa_pending_expires');
        Session::remove('2fa_pending_remember');

        AuthHelper::login($user);
        if ($remember) {
            AuthHelper::setRememberToken((int)$user['id']);
        }
        AuthHelper::checkAndRecordDevice((int)$user['id'], $request->ip(), $request->userAgent());
        AuthHelper::recordSecurityEvent((int)$user['id'], 'security.login_2fa', ['used_recovery_code' => $usedRecoveryCode]);
        $this->audit('user.login_2fa', 'user', $user['id'], ['used_recovery_code' => $usedRecoveryCode]);

        if ($usedRecoveryCode) {
            Session::flash('warning', 'You logged in using an emergency recovery code. Please generate new recovery codes in your security settings.');
        }

        if ((int)$user['force_password_change'] === 1) {
            redirect('/profile/force-password-change');
        }

        $this->redirectRoleHome();
    }

    public function logout(Request $request): never {
        $user = AuthHelper::user();
        if ($user) {
            AuthHelper::recordSecurityEvent((int)$user['id'], 'security.logout');
            $this->audit('user.logout', 'user', $user['id']);
        }
        AuthHelper::revokeRememberToken();
        AuthHelper::logout();
        redirect('/login');
    }

    public function showForgotPassword(Request $request): string {
        return Response::view('auth/forgot', [
            'title' => 'Account Recovery - Open LMS',
        ], null);
    }

    public function handleForgotPassword(Request $request): never {
        $email = strtolower(trim((string)$request->input('email')));
        $answer = trim((string)$request->input('recovery_answer'));
        $ip = $request->ip();

        if (empty($email) || empty($answer)) {
            Session::flash('error', 'Please provide both your account email and security answer.');
            redirect('/forgot-password');
        }

        $user = Database::fetchOne("SELECT id, email FROM users WHERE LOWER(email) = ? AND status = 'active' LIMIT 1", [$email]);
        $genericFail = "If the email and recovery answer match our records, you will be directed to reset your password.";

        if (!$user) {
            // Sleep slightly to prevent timing differences
            usleep(250000);
            Session::flash('error', 'Invalid account email or security answer.');
            redirect('/forgot-password');
        }

        // Check recovery attempts cooldown
        $since = time() - 300;
        $attemptCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM recovery_attempts WHERE user_id = ? AND attempted_at > ?",
            [$user['id'], $since]
        )['cnt'] ?? 0);

        if ($attemptCount >= 3) {
            Session::flash('error', 'Too many failed recovery attempts. Please wait 5 minutes before trying again.');
            redirect('/forgot-password');
        }

        // Check recovery question
        $rec = Database::fetchOne("SELECT * FROM recovery_questions WHERE user_id = ? LIMIT 1", [$user['id']]);
        if (!$rec) {
            Session::flash('error', 'No recovery security question was configured for this account. Please contact your LMS administrator.');
            redirect('/forgot-password');
        }

        $answerHash = AuthHelper::normalizeSecurityAnswer($answer);
        if (!hash_equals($rec['answer_hash'], $answerHash)) {
            Database::query("INSERT INTO recovery_attempts (user_id, ip_address, attempted_at) VALUES (?, ?, ?)", [$user['id'], $ip, time()]);
            Session::flash('error', 'Invalid account email or security answer.');
            redirect('/forgot-password');
        }

        // Successful verification! Set token in session
        Session::set('password_reset_user_id', (int)$user['id']);
        Session::set('password_reset_expires', time() + 600); // 10 minutes

        redirect('/reset-password');
    }

    public function showResetPassword(Request $request): string {
        $userId = Session::get('password_reset_user_id');
        $expires = Session::get('password_reset_expires', 0);

        if (!$userId || time() > $expires) {
            Session::remove('password_reset_user_id');
            redirect('/forgot-password');
        }

        return Response::view('auth/reset', [
            'title' => 'Create New Password - Open LMS',
        ], null);
    }

    public function handleResetPassword(Request $request): never {
        $userId = Session::get('password_reset_user_id');
        $expires = Session::get('password_reset_expires', 0);

        if (!$userId || time() > $expires) {
            Session::flash('error', 'Password reset session expired. Please verify your security answer again.');
            redirect('/forgot-password');
        }

        $password = (string)$request->input('password');
        $confirmation = (string)$request->input('password_confirmation');

        if (strlen($password) < 8) {
            Session::flash('error', 'Password must be at least 8 characters long.');
            redirect('/reset-password');
        }

        if ($password !== $confirmation) {
            Session::flash('error', 'Password confirmation does not match.');
            redirect('/reset-password');
        }

        $hash = AuthHelper::hashPassword($password);
        Database::query("UPDATE users SET password = ?, force_password_change = 0, updated_at = datetime('now') WHERE id = ?", [$hash, $userId]);

        Session::remove('password_reset_user_id');
        Session::remove('password_reset_expires');

        $this->audit('user.password_reset', 'user', $userId);
        Session::flash('success', 'Your password has been reset successfully. Please log in with your new password.');
        redirect('/login');
    }

    protected function redirectRoleHome(): never {
        $role = AuthHelper::role();
        if ($role === 'super_admin' || $role === 'admin') {
            redirect('/admin');
        } elseif ($role === 'teacher') {
            redirect('/teacher');
        } elseif ($role === 'student') {
            redirect('/student');
        } else {
            redirect('/login');
        }
    }
}
