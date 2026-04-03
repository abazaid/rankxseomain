<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Repositories\SaaSRepository;
use App\Repositories\StoreRepository;
use App\Services\Mailer;
use App\Services\SubscriptionManager;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;

final class AuthController
{
    public function loginForm(): void
    {
        if (!Database::isAvailable()) {
            Response::html(View::render('Login', '<div class="card"><h1>Login</h1><p class="muted">Database is not available yet.</p></div>'));
            return;
        }

        Response::html(View::render('Login', <<<HTML
<div class="card" style="max-width:620px;margin:auto;">
  <h1>Client Login</h1>
  <p class="muted">Login using your email and password.</p>
  <form method="post" action="/login">
    <label><strong>Email</strong></label>
    <input name="email" type="email" required>
    <label style="margin-top:10px;display:block;"><strong>Password</strong></label>
    <input name="password" type="password" required>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
      <button class="btn" type="submit">Login</button>
      <a class="btn btn-secondary" href="/register">Create Account</a>
      <a class="btn btn-secondary" href="/forgot-password">Forgot Password</a>
    </div>
  </form>
</div>
HTML));
    }

    public function registerForm(): void
    {
        if (!Database::isAvailable()) {
            Response::html(View::render('Register', '<div class="card"><h1>Register</h1><p class="muted">Database is not available yet.</p></div>'));
            return;
        }

        Response::html(View::render('Register', <<<HTML
<div class="card" style="max-width:680px;margin:auto;">
  <h1>Create Account</h1>
  <p class="muted">Standalone mode with direct email registration.</p>
  <form method="post" action="/register">
    <label><strong>Store Name</strong></label>
    <input name="store_name" type="text" required>
    <label style="margin-top:10px;display:block;"><strong>Full Name</strong></label>
    <input name="full_name" type="text" required>
    <label style="margin-top:10px;display:block;"><strong>Email</strong></label>
    <input name="email" type="email" required>
    <label style="margin-top:10px;display:block;"><strong>Password</strong></label>
    <input name="password" type="password" minlength="8" required>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
      <button class="btn" type="submit">Create</button>
      <a class="btn btn-secondary" href="/login">Back To Login</a>
    </div>
  </form>
</div>
HTML));
    }

    public function registerSubmit(): void
    {
        if (!Database::isAvailable()) {
            Response::html(View::render('Register', '<div class="card"><h1>Register</h1><p class="muted">Database is not available yet.</p></div>'), 500);
            return;
        }

        $storeName = trim((string) ($_POST['store_name'] ?? ''));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($storeName === '' || $fullName === '' || $email === '' || strlen($password) < 8) {
            Response::html(View::render('Register Error', '<div class="card"><h1>Invalid input</h1><p class="muted">Please fill all fields and use a password with at least 8 characters.</p><p><a class="btn" href="/register">Try again</a></p></div>'), 422);
            return;
        }

        $repo = new SaaSRepository();
        if ($repo->findUserByEmail($email) !== null) {
            Response::html(View::render('Register Error', '<div class="card"><h1>Email already exists</h1><p class="muted">Use Login or Forgot Password.</p><p><a class="btn" href="/login">Login</a></p></div>'), 409);
            return;
        }

        $merchantId = $this->generateStandaloneMerchantId($repo);
        $storeUsername = preg_replace('/[^a-z0-9]+/i', '-', strtolower($storeName));
        $storeUsername = trim((string) $storeUsername, '-');
        if ($storeUsername === '') {
            $storeUsername = 'store-' . $merchantId;
        }

        $storeId = $repo->upsertStore([
            'merchant_id' => $merchantId,
            'store_name' => $storeName,
            'store_username' => $storeUsername,
            'owner_email' => $email,
            'owner_name' => $fullName,
            'access_token' => null,
            'refresh_token' => null,
            'token_scope' => null,
            'token_expires_at' => null,
        ]);

        $user = $repo->ensureOwnerUser($storeId, $email, $fullName);
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            Response::html(View::render('Register Error', '<div class="card"><h1>Could not create account</h1></div>'), 500);
            return;
        }
        $repo->setUserPassword($userId, password_hash($password, PASSWORD_DEFAULT));

        $subscription = (new SubscriptionManager())->startTrial(['merchant_id' => $merchantId]);
        $repo->upsertSubscription($storeId, $subscription);

        (new StoreRepository())->save((string) $merchantId, [
            'merchant_id' => (string) $merchantId,
            'store' => [
                'name' => $storeName,
                'username' => $storeUsername,
            ],
            'settings' => [],
            'subscription' => $subscription,
            'usage_logs' => [],
            'standalone_items' => [
                'product' => [],
                'brand' => [],
                'category' => [],
            ],
            'created_at' => date(DATE_ATOM),
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['store_id'] = $storeId;
        header('Location: /member');
    }

    public function loginSubmit(): void
    {
        if (!Database::isAvailable()) {
            Response::json(['success' => false, 'message' => 'Database is not available.'], 500);
            return;
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $user = (new SaaSRepository())->findUserByEmail($email);

        if (!$user || empty($user['password_hash']) || !password_verify($password, (string) $user['password_hash'])) {
            Response::html(View::render('Login Failed', '<div class="card"><h1>Login failed</h1><p class="muted">Check email/password.</p><p><a class="btn" href="/login">Back</a></p></div>'), 401);
            return;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['store_id'] = (int) $user['store_id'];
        header('Location: /member');
    }

    public function setPasswordForm(): void
    {
        $token = (string) Request::query('token', '');

        if ($token === '' || !Database::isAvailable()) {
            Response::html(View::render('Set Password', '<div class="card"><h1>Invalid reset link</h1></div>'), 400);
            return;
        }

        $record = (new SaaSRepository())->findValidPasswordResetToken($token);
        if ($record === null) {
            Response::html(View::render('Set Password', '<div class="card"><h1>Reset link expired or invalid</h1></div>'), 400);
            return;
        }

        $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars((string) ($record['email'] ?? ''), ENT_QUOTES, 'UTF-8');

        Response::html(View::render('Set Password', <<<HTML
<div class="card" style="max-width:620px;margin:auto;">
  <h1>Set New Password</h1>
  <p class="muted">Account: {$safeEmail}</p>
  <form method="post" action="/set-password">
    <input type="hidden" name="token" value="{$safeToken}">
    <label><strong>New Password</strong></label>
    <input name="password" type="password" minlength="8" required>
    <div style="margin-top:18px;">
      <button class="btn" type="submit">Save Password</button>
    </div>
  </form>
</div>
HTML));
    }

    public function setPasswordSubmit(): void
    {
        if (!Database::isAvailable()) {
            Response::json(['success' => false, 'message' => 'Database is not available.'], 500);
            return;
        }

        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $repository = new SaaSRepository();
        $record = $repository->findValidPasswordResetToken($token);

        if ($record === null || strlen($password) < 8) {
            Response::html(View::render('Set Password', '<div class="card"><h1>Could not set password</h1><p class="muted">Check token and password length.</p></div>'), 422);
            return;
        }

        $repository->setUserPassword((int) $record['user_id'], password_hash($password, PASSWORD_DEFAULT));
        $repository->markResetTokenUsed((int) $record['id']);

        Response::html(View::render('Set Password', '<div class="card"><h1>Password updated</h1><p><a class="btn" href="/login">Login</a></p></div>'));
    }

    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['store_id']);
        header('Location: /login');
    }

    public function forgotPasswordForm(): void
    {
        Response::html(View::render('Forgot Password', <<<HTML
<div class="card" style="max-width:620px;margin:auto;">
  <h1>Forgot Password</h1>
  <p class="muted">Enter your email and we will send a reset link.</p>
  <form method="post" action="/forgot-password">
    <label><strong>Email</strong></label>
    <input name="email" type="email" required>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
      <button class="btn" type="submit">Send Link</button>
      <a class="btn btn-secondary" href="/login">Back To Login</a>
    </div>
  </form>
</div>
HTML));
    }

    public function forgotPasswordSubmit(): void
    {
        if (!Database::isAvailable()) {
            Response::html(View::render('Forgot Password', '<div class="card"><h1>Database is not available</h1></div>'), 500);
            return;
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $repository = new SaaSRepository();
        $user = $repository->findUserByEmail($email);

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $repository->createPasswordResetToken(
                (int) $user['id'],
                password_hash($rawToken, PASSWORD_DEFAULT),
                date('Y-m-d H:i:s', strtotime('+2 hours'))
            );

            $appUrl = rtrim((string) Config::get('APP_URL', 'http://localhost:8000'), '/');
            $url = $appUrl . '/set-password?token=' . urlencode($rawToken);
            (new Mailer())->sendPasswordReset((string) $user['email'], (string) ($user['full_name'] ?? ''), $url);
        }

        Response::html(View::render('Forgot Password', '<div class="card"><h1>Request sent</h1><p class="muted">If this email exists, a reset link has been sent.</p><p><a class="btn" href="/login">Back To Login</a></p></div>'));
    }

    public function dashboard(): void
    {
        if (!Database::isAvailable()) {
            Response::html(View::render('Dashboard', '<div class="card"><h1>Database is not available</h1></div>'), 500);
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $storeId = (int) ($_SESSION['store_id'] ?? 0);
        if ($userId <= 0 || $storeId <= 0) {
            header('Location: /login');
            return;
        }

        $store = (new SaaSRepository())->findStoreById($storeId);

        Response::html(View::renderFile('Dashboard', dirname(__DIR__) . '/Views/standalone-dashboard.php', [
            'storeName' => (string) ($store['store_name'] ?? 'Your Store'),
            'merchantId' => (string) ($store['merchant_id'] ?? '-'),
            'ownerEmail' => (string) ($store['owner_email'] ?? '-'),
        ]));
    }

    public function reconnect(): void
    {
        Response::json([
            'success' => false,
            'message' => 'This action is unavailable in standalone mode.',
        ], 400);
    }

    private function generateStandaloneMerchantId(SaaSRepository $repo): int
    {
        do {
            $candidate = random_int(100000000, 999999999);
            $existing = $repo->findStoreByMerchantId($candidate);
        } while ($existing !== null);

        return $candidate;
    }
}
