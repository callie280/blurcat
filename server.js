const express = require('express');
const nodemailer = require('nodemailer');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(bodyParser.json());
app.use(cors());

const PORT = 3000;

// Mock database for OTP
const otpDatabase = {};

// Configure nodemailer
const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: 'your-email@gmail.com', // Replace with your email
        pass: 'your-email-password'  // Replace with your app password
    }
});

// Route to send OTP
app.post('/send-otp', (req, res) => {
    const { email } = req.body;
    if (!email) {
        return res.status(400).send('Email is required');
    }

    // Generate a 6-digit OTP
    const otp = Math.floor(100000 + Math.random() * 900000);

    // Save OTP in mock database
    otpDatabase[email] = otp;

    // Send OTP via email
    const mailOptions = {
        from: 'your-email@gmail.com',
        to: email,
        subject: 'Password Reset OTP',
        text: `Your OTP for password reset is: ${otp}`
    };

    transporter.sendMail(mailOptions, (error, info) => {
        if (error) {
            console.error(error);
            return res.status(500).send('Failed to send OTP');
        }
        res.status(200).send('OTP sent successfully');
    });
});

// Route to verify OTP
app.post('/verify-otp', (req, res) => {
    const { email, otp } = req.body;

    if (!email || !otp) {
        return res.status(400).send('Email and OTP are required');
    }

    // Check if OTP matches
    if (otpDatabase[email] && otpDatabase[email] === parseInt(otp)) {
        delete otpDatabase[email]; // OTP verified, remove from database
        return res.status(200).send('OTP verified successfully');
    }

    res.status(400).send('Invalid OTP');
});

// Start server
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});

