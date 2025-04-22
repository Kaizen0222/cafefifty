<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Fifty Sports Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: url('https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/480785570_556000940801074_2294344429084287578_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeHzQolwyaDlStJu0t49Zz2KTT270hEWzUVNPbvSERbNRQOZ0RpaZLO4JtqSp0RZTlp89GLJ11EYkkLLW1PGdLj4&_nc_ohc=3RI3RXK2WycQ7kNvwHKYg_Y&_nc_oc=AdmPikTLkbU-RGusL279g37IcJePxJO4Z6XExWMqVjLj9tm0hSPvGyPAy0Qh6R1Td_8&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=WLaYHHalcoMWtU7F2n46vQ&oh=00_AfFVBQXGBT0saxXsuyJc89HtZ2uOtdlTszN7MPklUV-apg&oe=680B60BC') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: white;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.7);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }

        .header {
            position: fixed;
            top: 0;
            right: 20px;
            padding: 20px;
            z-index: 3;
            display: flex;
            gap: 15px;
        }

        .header a {
            background-color: rgba(0, 86, 179, 0.9);
            padding: 12px 20px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            text-align: center;
        }

        .header a:hover {
            background-color: #003f8a;
            transform: translateY(-2px);
        }

        .home-container {
            text-align: center;
            font-family: 'Times New Roman', Times, serif;
            padding: 50px 20px;
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .home-container h1 {
            font-family: 'Times New Roman', Times, serif;
            font-size: 4rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .home-container p {
            font-size: 1.5rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .book-button {
            display: inline-block;
            padding: 15px 40px;
            background-color: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .book-button:hover {
            background-color: #dc2f3d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .contact-info {
            margin-top: 30px;
            text-align: center;
        }

        .contact-info a {
            color: #ffd700;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .footer {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    
    <div class="header">
        <a href="user_auth/pages/login.php">Login</a>
        <a href="admin/index.php">Admin Login</a>
    </div>

    <div class="home-container">
        <h1>Cafe Fifty Sports Center</h1>
        <p>Schedule Your Next Sports Using Our Sports Complex</p>
        <p>Reserve Your Next Game!</p>
        <a href="/cafe_fifty/user_auth/pages/login.php" class="book-button">Book Now</a>

        <div class="contact-info">
            <p><strong>Location:</strong> <a href="https://www.google.com/maps?q=Pedro+Cinco+St,+Angat,+3012+Bulacan" target="_blank">View on Google Maps</a></p>
            <p><strong>Contact:</strong> <a href="tel:+63917 142 3190">+63 917 142 3190</a></p>
            <p><strong>Facebook:</strong> <a href="https://www.facebook.com/cafefiftysportscenter" target="_blank">Cafe Fifty Sports Center</a></p>
        </div>
    </div>

    <div class="footer">
        &copy; 2025 Cafe Fifty Sports Center. All rights reserved.
    </div>
</body>
</html>
