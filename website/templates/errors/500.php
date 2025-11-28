<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Phuket Station</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            color: #ef4444;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            margin: 0 8px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .icon {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="error-code">500</div>
        <h1>Something Went Wrong</h1>
        <p>We're experiencing technical difficulties. Please try again later.</p>
        <a href="/" class="btn">Back to Home</a>
        <a href="javascript:location.reload()" class="btn">Try Again</a>
    </div>
</body>
</html>
