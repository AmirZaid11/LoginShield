
# LoginShield - Secure Authentication System

LoginShield is a secure authentication system designed to provide robust user authentication using One-Time Passwords (OTPs) sent via email. It ensures secure access to user accounts by implementing OTP-based verification and session management.

---

## Features

- **User Registration**: Users can register with their email and password.
- **OTP-Based Login**: Users receive a One-Time Password (OTP) via email for secure login.
- **OTP Expiry**: OTPs expire after 10 minutes for enhanced security.
- **Session Management**: Secure session handling to protect user data.
- **Responsive UI**: Built with Tailwind CSS for a clean and responsive user interface.
- **Email Integration**: OTPs are sent to users via email using a custom email sender.

---

## Technologies Used

- **Backend**: PHP
- **Frontend**: HTML, Tailwind CSS
- **Database**: MySQL
- **Email Integration**: SMTP (via custom `send_email.php`)
- **Server**: XAMPP/Apache

---

## Installation

### Prerequisites

1. **XAMPP** (or any PHP/MySQL server environment).
2. **MySQL Database**.
3. **SMTP Credentials** (for sending emails).

### Steps

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/AmirZaid11/LoginShield.git
   cd LoginShield
   ```

2. **Set Up the Database**:
   - Import the `loginshield.sql` file into your MySQL database:
    

3. **Configure Database Connection**:
   - Update the `config/db.php` file with your database credentials:
     ```php
     <?php
     $host = "localhost";
     $username = "your_db_username";
     $password = "your_db_password";
     $dbname = "loginshield";

     $conn = new mysqli($host, $username, $password, $dbname);

     if ($conn->connect_error) {
         die("Connection failed: " . $conn->connect_error);
     }
     ?>
     ```

4. **Configure Email Settings**:
   - Update the `email/send_email.php` file with your SMTP credentials:
     ```php
     <?php
     function sendEmail($to, $subject, $message) {
         // Use PHPMailer or any other email library
         // Example using PHPMailer:
         require 'path/to/PHPMailer/src/PHPMailer.php';
         require 'path/to/PHPMailer/src/SMTP.php';

         $mail = new PHPMailer\PHPMailer\PHPMailer();
         $mail->isSMTP();
         $mail->Host = 'smtp.example.com';
         $mail->SMTPAuth = true;
         $mail->Username = 'your_email@example.com';
         $mail->Password = 'your_google_app_password';
         $mail->SMTPSecure = 'tls';
         $mail->Port = 587;

         $mail->setFrom('your_email@example.com', 'LoginShield');
         $mail->addAddress($to);
         $mail->isHTML(true);
         $mail->Subject = $subject;
         $mail->Body = $message;

         if ($mail->send()) {
             return true;
         } else {
             error_log("Email could not be sent. Error: " . $mail->ErrorInfo);
             return false;
         }
     }
     ?>
     ```

5. **Run the Application**:
   - Start your XAMPP server and navigate to `http://localhost/LoginShield` in your browser.

---

## Project Structure

```
LoginShield/
├── auth/
│   ├── login.php          # User login page
│   ├── register.php       # User registration page
│   ├── verify_otp.php     # OTP verification page
│   └── resend_otp.php     # Resend OTP functionality
├── dashboard/
│   └── index.php          # User dashboard
├── config/
│   └── db.php             # Database configuration
├── email/
│   └── send_email.php     # Email sending functionality
├── assets/                # Static files (CSS, JS, images)
├── README.md              # Project documentation
└── users.sql              # Database schema
```

---

## Usage

1. **Register a New User**:
   - Navigate to `http://localhost/LoginShield/auth/register.php`.
   - Enter your email and password to register.

2. **Login with OTP**:
   - Navigate to `http://localhost/LoginShield/auth/login.php`.
   - Enter your email and password.
   - An OTP will be sent to your email. Enter the OTP on the verification page.

3. **Access Dashboard**:
   - After successful OTP verification, you will be redirected to the dashboard.

4. **Resend OTP**:
   - If the OTP expires, click the "Resend OTP" button on the verification page.

---

## Screenshots


---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/YourFeatureName`).
3. Commit your changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature/YourFeatureName`).
5. Open a pull request.

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/) for the responsive UI.
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) for email integration.

---

## Contact

For any questions or feedback, please contact:

- Ernest Eddy  
- Email: **eddysimba9@gmail.com** 
- GitHub: **https://github.com/AmirZaid11**

---

Enjoy using LoginShield! 🚀
```

---
