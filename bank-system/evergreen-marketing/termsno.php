<?php
    session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']),
       'use_strict_mode' => true
    ]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agreement</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kulim+Park:ital,wght@0,200;0,300;0,400;0,600;0,700;1,200;1,300;1,400;1,600;1,700&display=swap" rel="stylesheet">
<style>
    :root {
        --bg-1: #003631;
        --bg-2: #0b7f6f;
        --card-bg: rgba(244,250,248,0.98);
        --text-dark: #083530;
        --text-muted: #36524e;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: linear-gradient(180deg, var(--bg-1) 0%, #0a8d79 45%, #26b59f 100%);
        font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
        min-height: 100vh;
        padding: 28px;
        -webkit-font-smoothing: antialiased;
    }

    /* Top Logo Bar */
    nav {
        position: fixed;
        left: 28px;
        top: 20px;
        display: flex;
        gap: 12px;
        align-items: center;
        z-index: 50;
    }

    nav img {
        width: 52px;
        height: 48px;
        border-radius: 50%;
    }

    .nav-wrap {
        display: flex;
        flex-direction: column;
    }

    .web-title a {
        color: #FFFFFF;
        font-family: "Kulim Park", sans-serif;
        font-weight: bold;
        font-size: 20px;
        text-decoration: none;
    }

    .motto a {
        color: rgba(230,255,249,0.9);
        font-size: 12px;
        margin-top: 2px;
        text-decoration: none;
    }

    /* Main Content */
    main {
        display: flex;
        justify-content: center;
        padding-top: 72px;
        max-width: 1150px;
        margin: 0 auto;
    }

    .main-container {
        background: var(--card-bg);
        border-radius: 14px;
        padding: 34px;
        width: 100%;
        box-shadow: 0 18px 44px rgba(2,24,20,0.32);
        border: 1px solid rgba(0,54,49,0.06);
        color: var(--text-dark);
    }

    .heading {
        color: var(--text-dark);
        font-family: "Kulim Park", sans-serif;
        font-size: 28px;
        margin-bottom: 8px;
        text-align: center;
    }

    .sub-heading {
        color: var(--text-muted);
        text-align: center;
        font-size: 13px;
        margin-bottom: 30px;
    }

    /* Scrollable Terms Content */
    .body-container {
        max-height: 60vh;
        overflow-y: auto;
        padding-right: 20px;
        margin-bottom: 30px;
    }

    .conditions-head {
        color: var(--text-dark);
        font-family: "Kulim Park", sans-serif;
        font-size: 15px;
        font-weight: 600;
        margin: 20px 0 8px;
    }

    .conditions-para {
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 12px;
    }

    /* Checkbox and Submit */
    .check-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid rgba(0,54,49,0.1);
    }

    .wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    label {
        color: var(--text-dark);
        font-size: 13px;
    }

    #agree {
          accent-color: #003631;
        }

    .submit {
        background: var(--bg-1);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .submit:hover {
        background: #004d45;
        transform: translateY(-1px);
    }

    /* Scrollbar Styling */
    .body-container::-webkit-scrollbar {
        width: 8px;
    }

    .body-container::-webkit-scrollbar-track {
        background: rgba(0,54,49,0.04);
        border-radius: 4px;
    }

    .body-container::-webkit-scrollbar-thumb {
        background: rgba(0,54,49,0.12);
        border-radius: 4px;
    }

    .body-container::-webkit-scrollbar-thumb:hover {
        background: rgba(0,54,49,0.2);
    }

    /* Responsive Design */
    @media (max-width: 968px) {
        body {
            padding: 20px;
        }

        nav {
            left: 20px;
            top: 15px;
            gap: 10px;
        }

        nav img {
            width: 44px;
            height: 40px;
        }

        .web-title a {
            font-size: 18px;
        }

        .motto a {
            font-size: 11px;
        }

        main {
            padding-top: 60px;
        }

        .main-container {
            padding: 28px;
        }

        .heading {
            font-size: 24px;
        }

        .sub-heading {
            font-size: 12px;
        }

        .body-container {
            max-height: 55vh;
            padding-right: 15px;
        }

        .conditions-head {
            font-size: 14px;
        }

        .check-container {
            justify-content: center;
        }
    }

    @media (max-width: 640px) {
        body {
            padding: 15px;
        }

        nav {
            left: 15px;
            top: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        nav img {
            width: 38px;
            height: 35px;
        }

        .web-title a {
            font-size: 16px;
        }

        .motto {
            display: none;
        }

        main {
            padding-top: 50px;
        }

        .main-container {
            padding: 22px;
            border-radius: 12px;
        }

        .heading {
            font-size: 20px;
            margin-bottom: 6px;
        }

        .sub-heading {
            font-size: 11px;
            margin-bottom: 20px;
        }

        .body-container {
            max-height: 50vh;
            padding-right: 12px;
            margin-bottom: 20px;
        }

        .conditions-head {
            font-size: 13px;
            margin: 15px 0 6px;
        }

        .conditions-para {
            font-size: 12px;
            margin-bottom: 10px;
        }

        .check-container {
            flex-direction: column;
            gap: 0.8rem;
            padding-top: 15px;
        }

        .wrap {
            width: 100%;
            justify-content: center;
        }

        label {
            font-size: 12px;
            text-align: center;
        }

        .submit {
            width: 100%;
            padding: 12px 24px;
        }

        .body-container::-webkit-scrollbar {
            width: 6px;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 12px;
        }

        nav {
            left: 12px;
            top: 10px;
        }

        nav img {
            width: 34px;
            height: 32px;
        }

        .web-title a {
            font-size: 14px;
        }

        main {
            padding-top: 45px;
        }

        .main-container {
            padding: 18px;
        }

        .heading {
            font-size: 18px;
        }

        .sub-heading {
            font-size: 10px;
        }

        .body-container {
            max-height: 45vh;
            padding-right: 10px;
        }

        .conditions-head {
            font-size: 12px;
        }

        .conditions-para {
            font-size: 11px;
        }
    }

    /* Landscape Orientation */
    @media (max-height: 600px) and (orientation: landscape) {
        .body-container {
            max-height: 35vh;
        }

        main {
            padding-top: 60px;
        }
    }
</style>
</head>
<body>
  <nav>
    <a href="viewing.php">
          <img src="images/icon.png" alt="logo" class="logo">
    </a>
    <div class="nav-wrap">
      <h2 class="web-title">
        <a href="viewing.php">EVERGREEN</a></h2>
      <p class="motto">
        <a href="viewing.php">Secure, Invest, Achieve</a></p>
    </div>
  </nav>

  <main>
    <!-- container for the terms -->
    <div class="main-container">
      <h1 class="heading">Terms and Agreement</h1>
      <p class="sub-heading">Please review our terms and conditions carefully before proceeding</p>
      <div class="body-container">
        <div class="wrap-tnc">
          <h3 class="conditions-head">1. Overview</h3>
          <p class="conditions-para">
            Welcome to our platform. These Terms and Agreement (“Terms”) outline the rules, conditions, and guidelines for accessing and using our services. By using or accessing this platform in any manner, you acknowledge that you have read, understood, and agreed to be bound by these Terms. If you do not agree with any part of these Terms, you must discontinue your use of the platform immediately.
          </p>
        </div>
        <div class="wrap-tnc">
          <h3 class="conditions-head">2. Acceptance of Terms</h3>
          <p class="conditions-para">By creating an account, accessing the platform, or using any of our services, you agree to comply with these Terms. Your continued use of the platform signifies your acceptance of any updated or modified Terms that may be implemented in the future. We reserve the right to update, change, or replace any part of these Terms at any time without prior notice. It is your responsibility to review these Terms periodically for changes.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">3. User Responsibilities</h3>
          <p class="conditions-para">As a user, you agree to:</p>
          <p class="conditions-para">Use the platform in a lawful and ethical manner.</p>
          <p class="conditions-para">Provide accurate, complete, and up-to-date information when required.</p>
          <p class="conditions-para">Maintain the confidentiality of any login credentials and be responsible for all activities under your account.</p>
          <p class="conditions-para">Avoid any actions that may disrupt, damage, or impair the platform’s services, security, or performance.</p>
          <p class="conditions-para">You are strictly prohibited from engaging in fraudulent activities, unauthorized access, hacking, distributing malicious software, or interfering with the proper functioning of the platform.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">4. Privacy &amp; Data Protection</h3>
          <p class="conditions-para">Your privacy is important to us. Any personal data collected during your use of this platform will be handled in accordance with our Privacy Policy. By using the platform, you consent to the collection, storage, use, and disclosure of your information as described in the Privacy Policy. We will take reasonable measures to protect your data; however, we cannot guarantee absolute security due to the nature of digital communications.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">5. Intellectual Property Rights</h3>
          <p class="conditions-para">All content available on this platform—including but not limited to text, images, graphics, logos, icons, design layout, software, and other materials—is the property of the platform or its licensors and is protected by copyright, trademark, and other intellectual property laws. You agree not to copy, reproduce, distribute, modify, or create derivative works from any content on the platform without prior written consent from the rightful owner.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">6. Restrictions &amp; Limitations</h3>
          <p class="conditions-para">You agree NOT to:</p>
          <p class="conditions-para">Use the platform for illegal, harmful, abusive, or misleading purposes.</p>
          <p class="conditions-para">Impersonate any person, entity, or misrepresent your affiliation.</p>
          <p class="conditions-para">Reverse-engineer, decompile, or tamper with the platform’s systems or features.</p>
          <p class="conditions-para">Upload or transmit any viruses, malware, or harmful code.</p>
          <p class="conditions-para">We reserve the right to restrict access, suspend, or terminate accounts found violating these Terms or engaging in suspicious activities.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">7. Termination of Use</h3>
          <p class="conditions-para">We may, at our sole discretion and without prior notice, suspend or terminate your access to the platform if we believe you have violated these Terms, engaged in unlawful behavior, or acted in a way that may harm the platform, other users, or our reputation. Upon termination, all rights granted under these Terms will immediately cease, and you must stop all use of the platform.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">8. Disclaimer of Warranties</h3>
          <p class="conditions-para">The platform is provided on an “as is” and “as available” basis. We do not warrant that:</p>
          <p class="conditions-para">The platform will always be secure, uninterrupted, or error-free.</p>
          <p class="conditions-para">Any defects or issues will be corrected.</p>
          <p class="conditions-para">The information provided is accurate, reliable, or up-to-date.</p>
          <p class="conditions-para">Your use of the platform is solely at your own risk. We disclaim all warranties, whether express or implied, including fitness for a particular purpose, merchantability, and non-infringement.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">9. Limitation of Liability</h3>
          <p class="conditions-para">To the fullest extent permitted by law, we shall not be liable for any damages—direct, indirect, incidental, special, consequential, or exemplary—that arise from your use or inability to use the platform. This includes, but is not limited to, loss of data, loss of profits, system failure, or any other damages, even if we have been advised of the possibility of such damages.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">10. Amendments &amp; Modifications</h3>
          <p class="conditions-para">We reserve the right to modify, update, or discontinue any part of the platform, services, or these Terms at any time. Any changes will be effective immediately upon posting on the platform. Your continued use after changes are posted constitutes acceptance of the revised Terms. We are not obligated to notify users individually about modifications.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">11. Governing Law &amp; Dispute Resolution</h3>
          <p class="conditions-para">These Terms and any disputes arising from them shall be governed by and interpreted in accordance with local applicable laws. Any disagreements or claims shall be resolved through good-faith negotiation first. If unresolved, the issue shall be settled through the appropriate legal process or arbitration, depending on jurisdiction.</p>
        </div>

        <div class="wrap-tnc">
          <h3 class="conditions-head">12. Contact Information</h3>
          <p class="conditions-para">If you have questions, concerns, feedback, or require clarification regarding these Terms, you may contact us through the following channels:</p>
          <p class="conditions-para">Email: support@example.com</p>
          <p class="conditions-para">Phone: (000) 000-0000</p>
        </div>
      </div>
      <div class="check-container">
        <div class="wrap">
          <label for="agree">I agree to the terms and condition</label>
          <input type="checkbox" name="agree" id="agree" value="agree" accent-color="#003631">
        </div>
        <button class="submit">Submit</button>
      </div>
    </div>
  </main>
</body>
<script>
  
</script>
</html>
