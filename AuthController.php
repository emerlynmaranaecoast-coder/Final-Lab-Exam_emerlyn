<?php
/** AuthController - login, logout and password change. */
class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard/index');
        }

        $error = '';
        $username = '';

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->requirePost();
            $username = $this->post('username');
            [$ok, $error] = Auth::attempt($username, (string) ($_POST['password'] ?? ''));

            if ($ok) {
                $target = $_SESSION['intended'] ?? 'dashboard/index';
                unset($_SESSION['intended']);
                $this->redirect(preg_match('/^[a-z]+\/[a-z]+$/', (string) $target) ? $target : 'dashboard/index');
            }
        }

        $this->render('auth/login', compact('error', 'username'), 'auth');
    }

    public function logout(): void
    {
        // Logging out is a state change, so it is POST + CSRF protected when logged in.
        if (Auth::check()) {
            $this->requirePost();
            Auth::logout();
        }
        header('Location: ' . url('auth/login'));
        exit;
    }

    public function password(): void
    {
        $errors = [];

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->requirePost();
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');

            if (strlen($new) < 8) {
                $errors['new_password'] = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $errors['confirm_password'] = 'Passwords do not match.';
            } elseif (!Auth::changePassword((int) Auth::user()['id'], $current, $new)) {
                $errors['current_password'] = 'Current password is incorrect.';
            }

            if (!$errors) {
                flash('success', 'Password changed successfully.');
                $this->redirect('dashboard/index');
            }
        }

        $this->render('auth/password', compact('errors'));
    }
}
