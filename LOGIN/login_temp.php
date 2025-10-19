<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <title>Document</title>
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--bg-color);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            width: 800px;
            max-width: 95%;
        }

        .login-left {
            background: linear-gradient(135deg, #7b083030, #b112273b), url('../img/1.jpg') no-repeat center;
            background-size: cover;
            flex: 1;
        }

        .login-right {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-title {
            text-align: center;
            color: #7b0830;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 8px;
            background-color: #e6e6e6;
            border: none;
        }

        .form-check-label a {
            color: #7b0830;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-login {
            background-color: #7b0830;
            color: white;
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-login:hover {
            background-color: #660627;
            color: white;
        }

        .btn-google {
            border: 1px solid #7b0830;
            color: #7b0830;
            border-radius: 8px;
            width: 100%;
        }

        .btn-google:hover {
            background-color: #7b0830;
            color: white;
        }

        .signup-text {
            text-align: center;
            margin-top: 20px;
        }

        .signup-text a {
            color: #7b0830;
            font-weight: 500;
            text-decoration: none;
        }

        .signup-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="login-left"></div>
        <div class="login-right">
            <h3 class="login-title">Login Form</h3>

            <form>
                <div class="mb-3">
                    <input type="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" placeholder="Password" required>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="small text-decoration-none" style="color:#7b0830;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-login mb-3">Login</button>

                <div class="text-center mb-3">or</div>

                <button type="button" class="btn btn-google mb-3">Login with Google</button>

                <div class="signup-text">
                    Create an Account <a href="#">Signup now</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>