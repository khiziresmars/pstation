<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Phuket Station</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: #fff;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            opacity: 0.3;
        }
        h1 {
            font-size: 32px;
            margin: 20px 0 10px;
        }
        p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: #fff;
            color: #0891b2;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .yacht-icon {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="yacht-icon">
            <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" stroke="white" stroke-width="2" opacity="0.3"/>
                <path d="M20 55 Q50 70 80 55 L75 65 Q50 75 25 65 Z" fill="white"/>
                <path d="M50 25 L50 52 L35 50 Z" fill="white" opacity="0.9"/>
                <path d="M50 25 L50 52 L65 48 Z" fill="white" opacity="0.7"/>
            </svg>
        </div>
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="/" class="btn">Back to Home</a>
    </div>
</body>
</html>
