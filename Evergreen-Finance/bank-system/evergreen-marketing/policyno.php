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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Privacy Policy - Evergreen</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Kulim+Park:wght@600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg-1:#003631;
      --bg-2:#0b7f6f;
      --card-bg: rgba(244,250,248,0.98);
      --accent: #F1B24A;
      --muted:#36524e;
    }
    *{
      box-sizing:border-box;
      margin: 0;
      padding: 0;
    }
    html,body{
      height:100%;
      font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial;
      color:#083530;
    }
    body{
      background: linear-gradient(180deg, var(--bg-1) 0%, #0a8d79 45%, #26b59f 100%);
      -webkit-font-smoothing:antialiased;
      padding:28px;
      display:flex;
      justify-content:center;
      align-items:flex-start;
      min-height: 100vh;
      width: 100%;
    }

    /* top-left logo small */
    .site-top{
      position:fixed;
      left:28px;
      top:20px;
      display:flex;
      gap:12px;
      align-items:center;
      color: #e6fff9;
      font-family: "Kulim Park",sans-serif;
      z-index:50;
    }

    .site-top img { 
      width:52px;
      height:48px;
      border-radius:50%;
      display:block; 
    }

    .site-top .brand a { 
      font-weight:bold;
      font-size:20px;
      line-height:1; 
      color: #ffffff; 
      text-decoration: none; 
    }
    
    .site-top .motto a { 
      font-size:12px; 
      color:rgba(230,255,249,0.9); 
      margin-top:4px; 
      font-weight:400; 
      text-decoration: none; 
    }

    .page-wrap{
      width:100%;
      max-width:1150px;
      align-self: center;
      margin-top: 80px;
    }

    .heading{
      text-align:center;
      color: #ffffff;
      margin-bottom:18px;
    }
    
    .heading h1{
      font-family:"Kulim Park",sans-serif;
      font-weight:700;
      margin:0 0 8px;
      color:#f7fff8;
      font-size: 50px;
    }
    .heading p{
      margin:0 auto;
      max-width:820px;
      color: rgba(230,255,249,0.9);
      font-size:13px;
      line-height:1.6;
    }

    /* policy card */
    .policy-card{
      margin-top:26px;
      background: var(--card-bg);
      border-radius:14px;
      padding:34px;
      box-shadow: 0 18px 44px rgba(2,24,20,0.32);
      border: 1px solid rgba(0,54,49,0.06);
      position:relative;
      overflow:visible;
      width: 100%;
    }

    .policy-columns{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:40px;
      position:relative;
    }
    /* center vertical divider */
    .policy-columns::before{
      content:"";
      position:absolute;
      left:50%;
      top:28px;
      bottom:28px;
      width:1px;
      background: rgba(0,54,49,0.06);
      transform:translateX(-50%);
      pointer-events:none;
    }

    .col {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .col h3{
      font-family:"Kulim Park",sans-serif;
      color:var(--bg-1);
      font-size:14px;
      margin:0 0 10px;
      font-weight:700;
    }
    .col p{
      margin:0 0 12px;
      color:var(--muted);
      font-size:13px;
      line-height:1.5;
    }
    .list-small{
      font-size:13px;
      color:var(--muted);
      margin:0 0 12px 0;
      padding-left:16px;
    }
    .list-small li{ margin:8px 0; }

    #gmail-link {
      color: #003631;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    #gmail-link:hover {
      color: var(--accent);
    }

    /* social icons bottom-right inside card */
    .card-footer{
      display:flex;
      justify-content:flex-end;
      align-items:center;
      gap:12px;
      margin-top:24px;
      padding-top: 20px;
      border-top: 1px solid rgba(0,54,49,0.06);
    }
    .social{
      display:flex;
      gap:10px;
      align-items:center;
    }
    .social a{
      display:inline-flex;
      border-radius:8px;
      background:rgba(0,54,49,0.04);
      color:var(--bg-1);
      align-items:center;
      justify-content:center;
      text-decoration:none;
      font-weight:600;
      font-size:14px;
      padding: 8px;
      transition: all 0.3s ease;
    }

    .social a:hover {
      background: rgba(0,54,49,0.1);
      transform: translateY(-2px);
    }

    .social img {
      width: 20px;
      height: 20px;
    }

    /* Responsive Design */
    @media (max-width: 968px) {
      body {
        padding: 20px;
      }

      .site-top {
        left: 20px;
        top: 15px;
        gap: 10px;
      }

      .site-top img {
        width: 44px;
        height: 40px;
      }

      .site-top .brand a {
        font-size: 18px;
      }

      .site-top .motto a {
        font-size: 11px;
      }

      .page-wrap {
        margin-top: 70px;
      }

      .heading h1 {
        font-size: 38px;
      }

      .heading p {
        font-size: 12px;
        max-width: 90%;
      }

      .policy-card {
        padding: 28px;
      }

      .policy-columns {
        gap: 30px;
      }

      .col h3 {
        font-size: 13px;
      }

      .col p,
      .list-small {
        font-size: 12px;
      }
    }

    @media (max-width: 768px) {
      .policy-columns {
        grid-template-columns: 1fr;
        gap: 25px;
      }

      .policy-columns::before {
        display: none;
      }

      .card-footer {
        justify-content: center;
      }
    }

    @media (max-width: 640px) {
      body {
        padding: 15px;
      }

      .site-top {
        left: 15px;
        top: 12px;
        flex-wrap: wrap;
        gap: 8px;
      }

      .site-top img {
        width: 38px;
        height: 35px;
      }

      .site-top .brand a {
        font-size: 16px;
      }

      .site-top .motto {
        display: none;
      }

      .page-wrap {
        margin-top: 60px;
      }

      .heading h1 {
        font-size: 28px;
        margin-bottom: 10px;
      }

      .heading p {
        font-size: 11px;
        max-width: 100%;
        padding: 0 10px;
      }

      .policy-card {
        padding: 22px;
        border-radius: 12px;
        margin-top: 20px;
      }

      .policy-columns {
        gap: 20px;
      }

      .col {
        gap: 16px;
      }

      .col h3 {
        font-size: 12px;
        margin-bottom: 8px;
      }

      .col p,
      .list-small {
        font-size: 11px;
        line-height: 1.5;
      }

      .list-small {
        padding-left: 14px;
      }

      .list-small li {
        margin: 6px 0;
      }

      .card-footer {
        margin-top: 18px;
        padding-top: 16px;
      }

      .social {
        gap: 8px;
      }

      .social a {
        padding: 6px;
      }

      .social img {
        width: 18px;
        height: 18px;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 12px;
      }

      .site-top {
        left: 12px;
        top: 10px;
      }

      .site-top img {
        width: 34px;
        height: 32px;
      }

      .site-top .brand a {
        font-size: 14px;
      }

      .page-wrap {
        margin-top: 55px;
      }

      .heading h1 {
        font-size: 24px;
      }

      .heading p {
        font-size: 10px;
      }

      .policy-card {
        padding: 18px;
      }

      .col h3 {
        font-size: 11px;
      }

      .col p,
      .list-small {
        font-size: 10px;
      }
    }

    /* Landscape Orientation */
    @media (max-height: 600px) and (orientation: landscape) {
      .page-wrap {
        margin-top: 70px;
      }

      .heading h1 {
        font-size: 32px;
        margin-bottom: 6px;
      }

      .heading p {
        font-size: 11px;
      }

      .policy-card {
        padding: 20px;
      }
    }

    /* Extra small devices */
    @media (max-width: 360px) {
      .heading h1 {
        font-size: 20px;
      }

      .policy-card {
        padding: 15px;
      }

      .col h3 {
        font-size: 10px;
      }

      .col p,
      .list-small {
        font-size: 9px;
      }
    }
  </style>
</head>
<body>
  <div class="site-top" aria-hidden="false">
    <a href="viewing.php">
      <img src="images/icon.png" alt="Evergreen logo">
    </a>
    <div>
      <div class="brand">
        <a href="viewing.php">EVERGREEN</a>
      </div>
      <div class="motto">
        <a href="viewing.php">Secure. Invest. Achieve</a></div>
    </div>
  </div>

  <div class="page-wrap" role="main">
    <header class="heading" aria-labelledby="policy-title">
      <h1 id="policy-title">Privacy Policy</h1>
      <p>At Evergreen we value your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, and safeguard your data when you use our website and services.</p>
    </header>

    <article class="policy-card" aria-labelledby="policy-title">
      <div class="policy-columns">
        <div class="col" aria-label="left column">
          <h3>1. Information We Collect</h3>
          <p>We collect information you provide when applying, registering, or communicating with us — name, contact details, identification, and financial data required to process services.</p>

          <h3>2. How We Use Your Information</h3>
          <ul class="list-small" aria-label="how we use info">
            <li>Process and verify loan or account applications</li>
            <li>Provide account notifications and support</li>
            <li>Deliver marketing where opted-in and improve our services</li>
          </ul>

          <h3>3. Data Protection</h3>
          <p>We use industry-standard encryption and strict access controls. Only authorized personnel access personal data and we continuously review security practices.</p>
        </div>

        <div class="col" aria-label="right column">
          <h3>4. Sharing & Third Parties</h3>
          <p>We do not sell personal data. We share information only with trusted service providers, regulators, or when required by law to deliver the requested services securely.</p>

          <h3>5. Your Rights</h3>
          <p>You can request access, correction, or deletion of your personal data. You may also opt out of promotional communications at any time.</p>

          <h3>6. Contact Us</h3>
          <p>If you have questions about this Privacy Policy, please contact us at <a href="#" id="gmail-link">evrgrn.64@gmail.com</a></p>
        </div>
      </div>

      <div class="card-footer" aria-hidden="true">
        <div class="social" role="presentation">
          <a href="https://www.facebook.com/profile.php?id=61582812214198">
            <img src="images/fbicon.png" alt="facebook">
        </a>
        <a href="https://www.instagram.com/evergreenbanking/">
            <img src="images/igicon.png" alt="instagram">
        </a>
        </div>
      </div>
    </article>
  </div>
</body>
</html>