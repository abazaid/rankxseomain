<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Controllers\StandaloneController;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'loginSubmit']);
$router->get('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/stores', [AdminController::class, 'stores']);
$router->get('/admin/stores/{id}', [AdminController::class, 'store']);
$router->post('/admin/stores/{id}/subscription', [AdminController::class, 'updateSubscription']);
$router->post('/admin/stores/{id}/adjust-quotas', [AdminController::class, 'adjustQuotas']);
$router->post('/admin/stores/{id}/delete', [AdminController::class, 'deleteStore']);
$router->get('/admin/activity', [AdminController::class, 'activity']);
$router->post('/admin/email-test', [AdminController::class, 'sendTestEmail']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'loginSubmit']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'registerSubmit']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'forgotPasswordSubmit']);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('/resend-verification', [AuthController::class, 'resendVerificationSubmit']);
$router->get('/set-password', [AuthController::class, 'setPasswordForm']);
$router->post('/set-password', [AuthController::class, 'setPasswordSubmit']);
$router->get('/member', [AuthController::class, 'dashboard']);
$router->get('/dashboard', [AuthController::class, 'dashboard']);

$router->get('/api/subscription', [StandaloneController::class, 'subscription']);
$router->get('/api/settings', [StandaloneController::class, 'settings']);
$router->post('/api/settings/save', [StandaloneController::class, 'saveSettings']);
$router->get('/api/operations', [StandaloneController::class, 'operations']);
$router->post('/api/items/generate', [StandaloneController::class, 'generateItem']);
$router->get('/api/items', [StandaloneController::class, 'listItems']);
$router->post('/api/keywords/research', [StandaloneController::class, 'keywordResearch']);
$router->get('/api/keywords/history', [StandaloneController::class, 'keywordHistory']);
$router->get('/api/domain-seo', [StandaloneController::class, 'domainSeo']);
$router->post('/api/domain-seo/save', [StandaloneController::class, 'saveDomainSeo']);
$router->post('/api/domain-seo/refresh', [StandaloneController::class, 'refreshDomainSeo']);
$router->get('/api/domain-seo/history', [StandaloneController::class, 'domainSeoHistory']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
