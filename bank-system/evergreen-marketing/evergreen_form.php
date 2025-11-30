<?php
    session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']),
       'use_strict_mode' => true
    ]);
    
    // Get user data from session if logged in
    $user_first_name = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : '';
    $user_last_name = isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '';
    $user_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
    $is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['email']);
?>

<html>
  <head>
    <meta charset="UTF-8">
    <title>Evergreen Form</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kulim+Park:ital,wght@0,200;0,300;0,400;0,600;0,700;1,200;1,300;1,400;1,600;1,700&display=swap" rel="stylesheet">
    <style>
      /* General */
      * {
        font-family: "Inter";
        color: #003631;
      }

      body {
        margin: 40px;
        background-image: url(images/bg-image.png);
        background-repeat: no-repeat;
        background-size: cover;
        background-attachment: fixed;
      }

      h1, h2, h3, h4, h5, h6, p {
        margin: 0;
      }

      /* NAVIGATION BAR */
      nav {
        display: flex;
        gap: 5px;
        margin: 20px;
      }

      nav img {
        width: 50%;
        height: 50%;
        border-radius: 50%;
      }

      .logo {
        width: 45px;
        height: 45px;
        align-self: center;
      }

      #title-page, .motto {
        font-family: "Kulim Park";
      }

      #title-page {
        font-size: 20px;
      }

      .motto {
        font-size: 12px;
      }

      /* FORM - General */
      main {
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .main-form-body {
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.5);
        padding: 50 40;
        width: 70%;
        display: flex;
        flex-direction: column;
        gap: 25px;
        position: relative;
      }

      /* FORM - Uppermost */

      .uppermost-form {
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 10px;
        position: absolute;
        justify-content: center;
        align-items: center;
        width: 90%;
      }

      .form-sub-text {
        color: #3A3A3A;
        font-size: 15px;
      }

      .form-title {
        font-size: 50px;
      }

      /* FORM - progress line */
      .upper-form {
        display: flex;
      }

      .wrap {
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 15px;
      }

      .form-part {
        /* background-color: #003631; for progress track */
        color: white;
        width: 40px;
        height: 40px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 20px;
      }

      label {
        margin-top: 5px;;
      }

      .form-part-label {
        font-size: 10px;
      }

      #form-part-I {
        background-color: #003631;
      }

      .form-line {
        /* background-color: #003631; for progress track */
        width: 80%;
        height: 2px;
        align-self: center;
        margin: 0 20px;
      }

      /* FORM - Personal Info Panel */
      .personal-info-panel {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-template-areas:
        "upper upper"
        "lower lower"
        "date date"
        "street street"
        "city city";
        gap: 15px;
        flex-direction: column;
      }

      .upper-input-wrap, .lower-input-wrap{
        display: flex;
        gap: 20px;
      }

      .city-input-wrap {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      .input-wrap {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 20px;
      }

      .upper-input-wrap {
        grid-area: upper;
      }

      .lower-input-wrap {
        grid-area: lower;
      }

      .date-input-wrap {
        grid-area: date;
     }

     .street-input-wrap {
        grid-area: street;
      }

      .city-input-wrap {
        grid-area: city;
      }

      .inp-credentials {
        width: 100%;
        height: 35px;
        border: 1px solid #C4C4C4;
        border-radius: 5px;
        padding: 5px 10px;
        font-size: 14px;
        margin-top: -10px;
      }

      .readonly-field {
        background-color: #f5f5f5;
        cursor: not-allowed;
        border-color: #d0d0d0;
        color: #666;
      }

      .readonly-field:focus {
        outline: none;
        border-color: #d0d0d0;
      }

      /* Verification section (Identity & Employment) */
      .verification-part {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding-top: 18px;
        border-top: 1px solid #E6E6E6;
      }

      .verification-part .section-title {
        color: #003631;
        font-size: 16px;
        margin: 6px 0 0 0;
        font-weight: 600;
      }

      .ssn-wrap {
        display: flex;
        flex-direction: column;
        gap: 15px;
      }

      .helper-text {
        font-size: 12px;
        color: #8C8C8C;
      }

      .two-col-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        align-items: center;
      }

      .full-width {
        width: 100%;
      }

      .emp-wrap {
        display: flex;
        flex-direction: column;
        gap: 15px;
      }

      /* FORM - Button */
      .btn-container {
        display: flex;
        justify-content: flex-start;
      }

      .button-action {
        border: none;
        border-radius: 5px;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
      }

      #button-next {
        background-color: #003631;
        color: white;
      }

      #button-prev {
        background-color: #E6E6E6;
        color: #003631;
      }

      /* REVIEW / Account Preferences */
      .review-part {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding-top: 18px;
        border-top: 1px solid #E6E6E6;
      }

      .account-type-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
      }

      .acct-card {
        border: 1px solid #E6E6E6;
        border-radius: 8px;
        padding: 14px;
        background: #FFFFFF;
        cursor: pointer;
        text-align: left;
        transition: 0.2s;
      }

      .acct-card h4 {
        margin: 0 0 6px 0;
        font-size: 14px;
      }

      .acct-card p {
        margin: 0;
        font-size: 12px;
        color: #8C8C8C;
      }

      /* if an acct-card is selected (FOR JAVASCRIPT) */
      .selected {
        background-color: #003631;
        color: white;
        border-color: #003631;
        transition: 0.2s;
      }

      .selected h4, .selected p {
        color: white;
      }

      /* Card selection options */
      .card-option {
        border: 2px solid #E6E6E6;
        border-radius: 8px;
        padding: 14px;
        background: #FFFFFF;
        cursor: pointer;
        text-align: left;
        transition: 0.2s;
        position: relative;
      }

      .card-option h4 {
        margin: 0 0 6px 0;
        font-size: 14px;
        color: #003631;
      }

      .card-option p {
        margin: 0 0 10px 0;
        font-size: 12px;
        color: #8C8C8C;
      }

      .card-option:hover {
        border-color: #003631;
        box-shadow: 0 2px 8px rgba(0, 54, 49, 0.1);
      }

      .card-option .card-checkbox {
        margin: 0;
        cursor: pointer;
      }

      .card-option.card-selected {
        background-color: #f0f8f7;
        border-color: #003631;
      }

      select.inp-credentials {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 35px;
        cursor: pointer;
      }

      select.inp-credentials:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background-color: #f5f5f5;
      }

      .services-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        align-items: start;
      }

      .terms-box {
        border: 1px solid #E6E6E6;
        border-radius: 6px;
        padding: 12px;
        background: #FBFBFB;
      }

      /* Error Text */
      .error-text {
        color: #D43F3A;
        font-size: 13px;
        margin-top: 6px;
        display: none; /* hidden until validation triggers */
      }

      .field-error {
        color: #D43F3A;
        font-size: 12px;
        display: none;
        margin-top: -10px;
      }

      .input-error {
        border-color: #D43F3A;
      }

      /* Review Modal - Modern Design */
      .modal-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 54, 49, 0.6);
        backdrop-filter: blur(8px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        padding: 20px;
      }

      .details-review {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafa 100%);
        border-radius: 24px;
        padding: 0;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px -12px rgba(0, 54, 49, 0.25),
                    0 0 0 1px rgba(0, 54, 49, 0.05);
        display: flex;
        flex-direction: column;
      }

      .modal-header {
        background: linear-gradient(135deg, #003631 0%, #005a50 100%);
        padding: 28px 32px;
        border-radius: 24px 24px 0 0;
        position: relative;
        overflow: hidden;
      }

      .modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
      }

      .modal-header h2 {
        color: white;
        font-size: 24px;
        font-weight: 600;
        margin: 0 0 8px 0;
        position: relative;
        z-index: 1;
      }

      .modal-header p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        margin: 0;
        position: relative;
        z-index: 1;
      }

      .details-contains {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        padding: 0;
      }

      .review-section {
        padding: 24px 28px;
        border-bottom: 1px solid #e8eeee;
      }

      .review-section:nth-child(odd) {
        border-right: 1px solid #e8eeee;
      }

      .review-section:last-child,
      .review-section:nth-last-child(2):nth-child(odd) {
        border-bottom: none;
      }

      .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
      }

      .section-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
      }

      .section-icon.personal {
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        color: #2e7d32;
      }

      .section-icon.address {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #1565c0;
      }

      .section-icon.identity {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #ef6c00;
      }

      .section-icon.employment {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        color: #7b1fa2;
      }

      .section-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: #003631;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .review-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
      }

      .review-grid.single-col {
        grid-template-columns: 1fr;
      }

      .review-item {
        background: white;
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid #e8eeee;
        transition: all 0.2s ease;
      }

      .review-item:hover {
        border-color: #003631;
        box-shadow: 0 4px 12px rgba(0, 54, 49, 0.08);
        transform: translateY(-1px);
      }

      .review-item.full-width {
        grid-column: span 2;
      }

      .review-item label {
        display: block;
        font-size: 11px;
        font-weight: 500;
        color: #6b7f7d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
      }

      .review-item .value {
        font-size: 15px;
        font-weight: 600;
        color: #003631;
        margin: 0;
        word-break: break-word;
      }

      .review-item .value.highlight {
        color: #005a50;
      }

      .modal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        background: #f8fafa;
        border-radius: 0 0 24px 24px;
        border-top: 1px solid #e8eeee;
      }

      .modal-footer .disclaimer {
        font-size: 12px;
        color: #6b7f7d;
        max-width: 400px;
      }

      .modal-footer .btn-group {
        display: flex;
        gap: 12px;
      }

      #cancel {
        background: white;
        color: #003631;
        padding: 12px 28px;
        border-radius: 12px;
        border: 2px solid #e8eeee;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      #cancel:hover {
        background: #f8fafa;
        border-color: #003631;
      }

      #ok {
        background: linear-gradient(135deg, #003631 0%, #005a50 100%);
        color: white;
        padding: 12px 32px;
        border-radius: 12px;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 14px rgba(0, 54, 49, 0.3);
      }

      #ok:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 54, 49, 0.4);
      }

      #ok:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
      }

      /* Location badge styling */
      .location-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .location-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f0f7f6;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: #003631;
      }

      .location-badge .badge-label {
        font-weight: 400;
        color: #6b7f7d;
      }

      .location-badge .badge-value {
        font-weight: 600;
      }

      /* Responsive modal */
      @media (max-width: 768px) {
        .details-review {
          width: 95%;
          max-height: 95vh;
        }

        .details-contains {
          grid-template-columns: 1fr;
        }

        .review-section:nth-child(odd) {
          border-right: none;
        }

        .review-grid {
          grid-template-columns: 1fr;
        }

        .review-item.full-width {
          grid-column: span 1;
        }

        .modal-footer {
          flex-direction: column;
          gap: 16px;
        }

        .modal-footer .disclaimer {
          text-align: center;
          max-width: none;
        }

        .modal-footer .btn-group {
          width: 100%;
        }

        #cancel, #ok {
          flex: 1;
        }
      }

      /* Success modal - Modern Design */
      .successful-modal {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafa 100%);
        border-radius: 24px;
        padding: 48px 40px;
        width: 90%;
        max-width: 480px;
        box-shadow: 0 25px 50px -12px rgba(0, 54, 49, 0.25);
        display: flex;
        justify-content: center;
        flex-direction: column;
        align-items: center;
        gap: 24px;
        text-align: center;
      }

      .success-icon-wrap {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
      }

      .success-icon-wrap::before {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(46, 125, 50, 0.1) 0%, rgba(46, 125, 50, 0.05) 100%);
        animation: pulse-ring 2s ease-out infinite;
      }

      @keyframes pulse-ring {
        0% { transform: scale(0.9); opacity: 1; }
        100% { transform: scale(1.2); opacity: 0; }
      }

      .check {
        width: 50px;
        height: 50px;
        position: relative;
        z-index: 1;
      }

      .success-content {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      .head-text {
        font-size: 28px;
        font-weight: 700;
        color: #003631;
        margin: 0;
      }

      .sub-text {
        font-size: 16px;
        color: #6b7f7d;
        margin: 0;
        max-width: 320px;
        line-height: 1.5;
      }

      .s-wrap {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
        background: #f0f7f6;
        padding: 16px 24px;
        border-radius: 12px;
        width: 100%;
      }

      .s-wrap .grey-text {
        font-size: 13px;
        color: #6b7f7d;
        margin: 0;
      }

      .s-wrap .grey-text strong {
        color: #003631;
        font-weight: 600;
      }

      #confirm-btn {
        background: linear-gradient(135deg, #003631 0%, #005a50 100%);
        color: white;
        padding: 16px 48px;
        border-radius: 12px;
        border: none;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 14px rgba(0, 54, 49, 0.3);
        width: 100%;
        max-width: 280px;
      }

      #confirm-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 54, 49, 0.4);
      }

      /* Back button */
      .back-container {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 5%;
        z-index: 9999;
      }

      .back-link {
        font-size: 20px;
        text-decoration: none;
        color: #003631;
      }

      /* Extras */
      .progress {
        color: black;
        background-color: white;
      }

      .text-progress {
        color: white;
        background-color: #003631;
      }

      /* Animation Keyframes */
      @keyframes fadeOut {
        from {
          opacity: 1;
          transform: scale(1);
        }
        to {
          opacity: 0;
          transform: scale(0.95);
        }
      }

      @keyframes fadeIn {
        from {
          opacity: 0;
        }
        to {
          opacity: 1;
        }
      }

      @keyframes slideUp {
        from {
          opacity: 0;
          transform: translateY(30px) scale(0.95);
        }
        to {
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }

      @keyframes checkmarkPop {
        0% {
          transform: scale(0) rotate(0deg);
          opacity: 0;
        }
        50% {
          transform: scale(1.2) rotate(180deg);
        }
        100% {
          transform: scale(1) rotate(360deg);
          opacity: 1;
        }
      }

      @keyframes pulse {
        0%, 100% {
          transform: scale(1);
        }
        50% {
          transform: scale(1.05);
        }
      }

      @keyframes shimmer {
        0% {
          background-position: -1000px 0;
        }
        100% {
          background-position: 1000px 0;
        }
      }

      /* Modal Container Animation */
      .modal-container {
        animation: fadeIn 0.3s ease;
      }

      .modal-container.closing {
        animation: fadeOut 0.3s ease forwards;
      }

      /* Details Review Animation */
      .details-review {
        animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      }

      /* Success Modal Animation */
      .successful-modal {
        animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      }

      /* Checkmark Animation */
      .check {
        animation: checkmarkPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s backwards;
        width: 25%;
        height: 25%;
      }

      /* Success Text Animation */
      .head-text {
        animation: slideUp 0.5s ease 0.3s backwards;
      }

      .sub-text {
        animation: slideUp 0.5s ease 0.4s backwards;
      }

      .s-wrap {
        animation: slideUp 0.5s ease 0.5s backwards;
      }

      #confirm-btn {
        animation: slideUp 0.5s ease 0.6s backwards;
        transition: all 0.3s ease;
      }

      #confirm-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 54, 49, 0.3);
      }

      /* Loading State for Submit Button */
      .button-action.submitting {
        position: relative;
        color: transparent;
        pointer-events: none;
        background: linear-gradient(90deg, #003631 0%, #1a6b62 50%, #003631 100%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
      }

      .button-action.submitting::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 3px solid transparent;
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
      }

      @keyframes spin {
        to {
          transform: translate(-50%, -50%) rotate(360deg);
        }
      }

      /* Panel Transitions */
      .personal-info-panel,
      .verification-part,
      .review-part {
        animation: fadeInPanel 0.4s ease;
      }

      @keyframes fadeInPanel {
        from {
          opacity: 0;
          transform: translateX(-20px);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      /* Success Confetti Effect (Optional) */
      @keyframes confetti-fall {
        0% {
          transform: translateY(-100vh) rotate(0deg);
          opacity: 1;
        }
        100% {
          transform: translateY(100vh) rotate(720deg);
          opacity: 0;
        }
      }

      .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        background: #F1B24A;
        position: absolute;
        animation: confetti-fall 3s linear forwards;
      }

      /* Responsive Design */
      @media (max-width: 968px) {
        body {
          margin: 20px;
        }

        nav {
          margin: 10px;
          gap: 8px;
        }

        nav img {
          width: 44px;
          height: 44px;
        }

        .logo {
          width: 40px;
          height: 40px;
        }

        #title-page {
          font-size: 18px;
        }

        .motto {
          font-size: 11px;
        }

        .main-form-body {
          width: 85%;
          padding: 40px 30px;
          gap: 20px;
        }

        .form-title {
          font-size: 42px;
        }

        .form-sub-text {
          font-size: 14px;
        }

        .upper-form {
          flex-wrap: wrap;
          justify-content: center;
        }

        .wrap {
          gap: 12px;
        }

        .form-part {
          width: 35px;
          height: 35px;
        }

        .form-part-label {
          font-size: 9px;
        }

        .form-line {
          width: 60%;
          margin: 0 10px;
        }

        .personal-info-panel {
          gap: 12px;
        }

        .upper-input-wrap,
        .lower-input-wrap {
          flex-direction: column;
          gap: 15px;
        }

        .city-input-wrap {
          grid-template-columns: 1fr;
          gap: 12px;
        }

        .inp-credentials {
          height: 32px;
          font-size: 13px;
        }

        .verification-part,
        .review-part {
          gap: 15px;
        }

        .section-title {
          font-size: 15px;
        }

        .two-col-row {
          grid-template-columns: 1fr;
          gap: 12px;
        }

        .account-type-cards {
          grid-template-columns: 1fr;
          gap: 10px;
        }

        .services-grid {
          grid-template-columns: 1fr;
          gap: 10px;
        }

        .details-review {
          width: 70%;
        }

        .details-contains {
          flex-direction: column;
          gap: 20px;
        }

        .successful-modal {
          width: 60%;
          padding: 25px;
        }
      }

      @media (max-width: 640px) {
        body {
          margin: 15px;
        }

        nav {
          margin: 8px;
          flex-wrap: wrap;
          gap: 6px;
        }

        nav img {
          width: 38px;
          height: 38px;
        }

        .logo {
          width: 35px;
          height: 35px;
        }

        #title-page {
          font-size: 16px;
        }

        .motto {
          display: none;
        }

        main {
          padding: 10px 0;
        }

        .main-form-body {
          width: 95%;
          padding: 30px 20px;
          gap: 18px;
        }

        .uppermost-form {
          width: 100%;
          gap: 8px;
        }

        .form-title {
          font-size: 32px;
          line-height: 1.1;
        }

        .form-sub-text {
          font-size: 13px;
        }

        .upper-form {
          gap: 10px;
        }

        .form-part {
          width: 30px;
          height: 30px;
          font-size: 14px;
        }

        .form-part-label {
          font-size: 8px;
        }

        .form-line {
          width: 50%;
          margin: 0 8px;
        }

        .personal-info-panel {
          grid-template-columns: 1fr;
          gap: 10px;
        }

        .upper-input-wrap,
        .lower-input-wrap {
          gap: 12px;
        }

        .city-input-wrap {
          grid-template-columns: 1fr;
          gap: 12px;
        }

        .input-wrap {
          gap: 15px;
        }

        label {
          font-size: 13px;
          margin-top: 3px;
        }

        .inp-credentials {
          height: 30px;
          font-size: 12px;
          padding: 4px 8px;
        }

        .verification-part,
        .review-part {
          gap: 12px;
          padding-top: 15px;
        }

        .section-title {
          font-size: 14px;
        }

        .helper-text {
          font-size: 11px;
        }

        .ssn-wrap,
        .emp-wrap {
          gap: 12px;
        }

        .account-type-cards {
          gap: 8px;
        }

        .acct-card {
          padding: 12px;
        }

        .acct-card h4 {
          font-size: 13px;
        }

        .acct-card p {
          font-size: 11px;
        }

        .services-grid {
          gap: 8px;
        }

        .services-grid label {
          font-size: 12px;
        }

        .terms-box {
          padding: 10px;
        }

        .terms-box label {
          font-size: 12px;
          gap: 6px;
        }

        .button-action {
          padding: 8px 16px;
          font-size: 14px;
        }

        .btn-container {
          flex-direction: column;
          gap: 10px;
        }

        .back-link {
          font-size: 18px;
        }

        .details-review {
          width: 90%;
          padding: 18px;
        }

        .confirm-title {
          font-size: 18px;
          margin-bottom: 15px;
        }

        .details-contains {
          padding: 15px;
          gap: 15px;
        }

        .content-wrap label {
          font-size: 12px;
        }

        .content-wrap h4 {
          font-size: 14px;
        }

        .content-group {
          flex-direction: column;
          gap: 12px;
        }

        #ok, #cancel {
          width: 100%;
          padding: 10px;
        }

        .successful-modal {
          width: 85%;
          padding: 20px;
          gap: 12px;
        }

        .check {
          width: 50px;
          height: 50px;
        }

        .head-text {
          font-size: 20px;
        }

        .sub-text {
          font-size: 14px;
          text-align: center;
        }

        .s-wrap {
          gap: 4px;
        }

        .grey-text {
          font-size: 12px;
        }

        #confirm-btn {
          width: 100%;
          padding: 10px 20px;
        }
      }

      @media (max-width: 480px) {
        body {
          margin: 12px;
        }

        nav img {
          width: 34px;
          height: 34px;
        }

        .logo {
          width: 32px;
          height: 32px;
        }

        #title-page {
          font-size: 14px;
        }

        .main-form-body {
          width: 100%;
          padding: 25px 15px;
          gap: 15px;
        }

        .form-title {
          font-size: 28px;
          margin-bottom: 8px;
        }

        .form-sub-text {
          font-size: 12px;
        }

        .form-part {
          width: 28px;
          height: 28px;
          font-size: 13px;
        }

        .form-part-label {
          font-size: 7px;
        }

        .form-line {
          width: 40%;
          margin: 0 5px;
        }

        .personal-info-panel {
          gap: 8px;
        }

        .input-wrap {
          gap: 12px;
        }

        label {
          font-size: 12px;
        }

        .inp-credentials {
          height: 28px;
          font-size: 11px;
        }

        .section-title {
          font-size: 13px;
        }

        .helper-text {
          font-size: 10px;
        }

        .acct-card {
          padding: 10px;
        }

        .acct-card h4 {
          font-size: 12px;
        }

        .acct-card p {
          font-size: 10px;
        }

        .services-grid label {
          font-size: 11px;
        }

        .terms-box {
          padding: 8px;
        }

        .terms-box label {
          font-size: 11px;
        }

        .button-action {
          padding: 7px 14px;
          font-size: 13px;
        }

        .back-link {
          font-size: 16px;
        }

        .details-review {
          width: 95%;
          padding: 15px;
        }

        .confirm-title {
          font-size: 16px;
        }

        .details-contains {
          padding: 12px;
        }

        .content-wrap label {
          font-size: 11px;
        }

        .content-wrap h4 {
          font-size: 13px;
        }

        .successful-modal {
          width: 95%;
          padding: 18px;
        }

        .head-text {
          font-size: 18px;
        }

        .sub-text {
          font-size: 13px;
        }

        .grey-text {
          font-size: 11px;
        }
      }

      @media (max-width: 360px) {
        .form-title {
          font-size: 24px;
        }

        .form-sub-text {
          font-size: 11px;
        }

        .form-part {
          width: 26px;
          height: 26px;
          font-size: 12px;
        }

        .inp-credentials {
          height: 26px;
          font-size: 10px;
        }

        .section-title {
          font-size: 12px;
        }

        .acct-card h4 {
          font-size: 11px;
        }

        .acct-card p {
          font-size: 9px;
        }

        .button-action {
          font-size: 12px;
          padding: 6px 12px;
        }

        .head-text {
          font-size: 16px;
        }

        .sub-text {
          font-size: 12px;
        }
      }

      /* Landscape Orientation */
      @media (max-height: 600px) and (orientation: landscape) {
        body {
          margin: 15px 20px;
        }

        .main-form-body {
          padding: 30px 25px;
        }

        .form-title {
          font-size: 36px;
          margin-bottom: 8px;
        }

        .form-sub-text {
          font-size: 13px;
        }

        .upper-form {
          margin: 15px 0;
        }

        .personal-info-panel {
          gap: 10px;
        }

        .verification-part,
        .review-part {
          gap: 10px;
        }
      }

      /* Very small landscape phones */
      @media (max-width: 640px) and (orientation: landscape) {
        .form-title {
          font-size: 28px;
        }

        .upper-form {
          margin: 10px 0;
        }
      }

      /* Improve touch targets on mobile */
      @media (hover: none) and (pointer: coarse) {
        .button-action,
        .acct-card,
        input[type="checkbox"],
        .back-link {
          min-height: 44px;
          min-width: 44px;
        }

        .faq-toggle {
          min-height: 44px;
          min-width: 44px;
        }
      }

    </style>
  </head>
  <body>
    <nav>
      <img src="images/loginlogo.png" alt="logo" class="logo">
      <div class="wrap-nav">
        <h1 id="title-page">EVERGREEN</h1>
        <P class="motto">Secure, Invest, Achieve</P>
      </div>
    </nav>

    <!-- main body -->
    <main>
      <div class="main-form-body">
        <div class="back-container">
            <a href="viewingpage.php" class="back-link"><-</a>
        </div>
        <!-- form title -->
        <div class="uppermost-form">
          <h2 class="form-title">Account Application</h2>
          <p class="form-sub-text">Complete this application to open your Evergreen Bank account</p>
        </div>
        <!-- form progress -->
        <!-- make it like a breadcrumb logic --> 
        <!-- the form part 2 & 3 should be grey at first -->
        <div class="upper-form">
          <div class="wrap">
            <h4 class="form-part" id="form-part-I">1</h4>
            <p class="form-part-label" id="label-I">Personal Info</p>
          </div>
          <hr class="form-line" id="line-I">
          <div class="wrap">
            <h4 class="form-part" id="form-part-II">2</h4>
            <p class="form-part-label" id="label-II">Verification</p>
          </div>
          <hr class="form-line" id="line-II">
          <div class="wrap">
            <h4 class="form-part" id="form-part-III">3</h4>
            <p class="form-part-label" id="label-III">Review</p>
          </div>
        </div>

        <!-- this will be the replaceable panel -->
        <div class="fillup-change">
          
          <!-- Personal info part -->
          <div class="personal-info-panel">
            <div class="upper-input-wrap">
              <div class="input-wrap">
                <label for="f-name">First Name<span style="color: red;">*</span></label>
                <input type="text" class="inp-credentials <?php echo $is_logged_in ? 'readonly-field' : ''; ?>" id="f-name" value="<?php echo $user_first_name; ?>" <?php echo $is_logged_in ? 'readonly' : ''; ?>>
              </div>
              <div class="input-wrap">
                <label for="l-name">Last Name<span style="color: red;">*</span></label>
                <input type="text" class="inp-credentials <?php echo $is_logged_in ? 'readonly-field' : ''; ?>" id="l-name" value="<?php echo $user_last_name; ?>" <?php echo $is_logged_in ? 'readonly' : ''; ?>>
              </div>
            </div>

            <div class="lower-input-wrap">
              <div class="input-wrap">
                <label for="e-mail">Email Address<span style="color: red;">*</span></label>
                <input type="email" class="inp-credentials <?php echo $is_logged_in ? 'readonly-field' : ''; ?>" id="e-mail" value="<?php echo $user_email; ?>" <?php echo $is_logged_in ? 'readonly' : ''; ?>>
              </div>
              <div class="input-wrap">
                <label for="phone-number">Phone Number<span style="color: red;">*</span></label>
                <input type="tel" class="inp-credentials" id="phone-number" placeholder="(123) 456-7890">
            </div>
          </div>

          <div class="date-input-wrap">
              <div class="input-wrap">
                <label for="date-of-birth">Date of Birth<span style="color: red;">*</span></label>
                <input type="date" class="inp-credentials" id="date-of-birth" max="">
                <span class="helper-text" style="font-size: 11px; color: #666; margin-top: 4px;">You must be at least 18 years old</span>
              </div>
            </div>

            <div class="city-input-wrap">
              <div class="input-wrap">
                <label for="region">Region<span style="color: red;">*</span></label>
                <select class="inp-credentials" id="region">
                  <option value="">Select Region</option>
                </select>
              </div>
              <div class="input-wrap">
                <label for="state">Province<span style="color: red;">*</span></label>
                <select class="inp-credentials" id="state" disabled>
                  <option value="">Select Province</option>
                </select>
              </div>
              <div class="input-wrap">
                <label for="city">City/Municipality<span style="color: red;">*</span></label>
                <select class="inp-credentials" id="city" disabled>
                  <option value="">Select City/Municipality</option>
                </select>
              </div>
              <div class="input-wrap">
                <label for="barangay">Barangay<span style="color: red;">*</span></label>
                <select class="inp-credentials" id="barangay" disabled>
                  <option value="">Select Barangay</option>
                </select>
              </div>
            </div>

            <div class="street-input-wrap">
              <div class="input-wrap">
                <label for="street-address">Street Address / House No.<span style="color: red;">*</span></label>
                <input type="text" class="inp-credentials" id="street-address" placeholder="e.g., 123 Main Street, Block 5 Lot 10">
              </div>
              <div class="input-wrap">
                <label for="zip-code">Zip Code<span style="color: red;">*</span></label>
                <input type="text" class="inp-credentials" id="zip-code" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
              </div>
            </div>
          </div>
          <!-- Verification Part -->
<div class="verification-part" style="display: none;">
  <!-- Identity Section -->
  <h3 class="section-title">Identity Verification</h3>
  
  <div class="ssn-wrap">
    <div class="input-wrap">
      <label for="ssn">Social Security Number<span style="color: red;">*</span></label>
      <input type="text" id="ssn" class="inp-credentials" placeholder="123-45-6789">
    </div>
    <div class="helper-text">Your SSN is securely encrypted and never shared with third parties.</div>
  </div>

  <div class="two-col-row">
    <div class="input-wrap">
      <label for="id-type">ID Type<span style="color: red;">*</span></label>
      <select id="id-type" class="inp-credentials">
        <option>Driver's License</option>
        <option>Passport</option>
        <option>State ID</option>
      </select>
    </div>
    <div class="input-wrap">
      <label for="id-number">ID Number<span style="color: red;">*</span></label>
      <input type="text" id="id-number" class="inp-credentials">
    </div>
  </div>

  <!-- Employment Section -->
  <h3 class="section-title">Employment Information</h3>

  <div class="emp-wrap">
    <div class="input-wrap">
      <label for="employment-status">Employment Status<span style="color: red;">*</span></label>
      <select id="employment-status" class="inp-credentials full-width">
        <option>Employed</option>
        <option>Unemployed</option>
        <option>Self-Employed</option>
        <option>Student</option>
        <option>Retired</option>
      </select>
    </div>
  </div>

  <div class="two-col-row">
    <div class="input-wrap">
      <label for="employer-name">Employer Name<span style="color: red;">*</span></label>
      <input type="text" id="employer-name" class="inp-credentials">
    </div>
    <div class="input-wrap">
      <label for="job-title">Job Title<span style="color: red;">*</span></label>
      <input type="text" id="job-title" class="inp-credentials">
    </div>
  </div>

  <div class="input-wrap">
    <label for="annual-income">Annual Income (USD)<span style="color: red;">*</span></label>
    <input type="text" id="annual-income" class="inp-credentials full-width" placeholder="50000">
  </div>
</div>
           <!-- Review Part -->
            <div class="review-part" style="display: none;">
              <h3 class="section-title">Account Preferences</h3>

              <div class="account-type-cards">
                <div class="acct-card" id="acct-checking">
                  <h4>Checking</h4>
                  <p>Everyday banking</p>
                </div>
                <div class="acct-card" id="acct-savings">
                  <h4>Savings</h4>
                  <p>Earn interest</p>
                </div>
                <div class="acct-card" id="acct-both">
                  <h4>Both</h4>
                  <p>Complete package</p>
                </div>
              </div>

              <div>
                <h3 class="section-title">Card Selection</h3>
                <p style="margin:8px 0 12px 0; font-size:13px; color:#666;">Choose the cards you want to apply for</p>
                <div class="account-type-cards">
                  <div class="card-option" data-card="debit">
                    <h4>Debit Card</h4>
                    <p>Access your funds instantly</p>
                    <input type="checkbox" value="debit" class="card-checkbox" style="accent-color: #003631">
                  </div>
                  <div class="card-option" data-card="credit">
                    <h4>Credit Card</h4>
                    <p>Build credit & earn rewards</p>
                    <input type="checkbox" value="credit" class="card-checkbox" style="accent-color: #003631">
                  </div>
                  <div class="card-option" data-card="prepaid">
                    <h4>Prepaid Card</h4>
                    <p>Control your spending</p>
                    <input type="checkbox" value="prepaid" class="card-checkbox" style="accent-color: #003631">
                  </div>
                </div>
              </div>

              <div>
                <p style="margin:8px 0 6px 0; font-size:13px; color:#003631;">Additional Services (Optional)</p>
                <div class="services-grid">
                  <label><input type="checkbox" value="online" style="accent-color: #003631"> Online Banking</label>
                  <label><input type="checkbox" value="mobile" style="accent-color: #003631">Mobile Banking</label>
                  <label><input type="checkbox" value="overdraft" style="accent-color: #003631"> Overdraft Protection</label>
                  <label><input type="checkbox" value="alerts" style="accent-color: #003631"> SMS Alerts</label>
                </div>
              </div>

              <div>
                <h3 class="section-title">Terms and Agreements</h3>
                <div class="terms-box">
                  <label style="display:flex; gap:8px; align-items:flex-start;"><input type="checkbox" value="I agree" id="term-tnc" style="accent-color: #003631"> I agree to the <strong>Terms and Conditions</strong> of Evergreen Bank.</label>
                  <p style="color: red;" id="error-tnc">agree to terms and condition</p>
                  <label style="display:flex; gap:8px; align-items:flex-start; margin-top:8px;"><input type="checkbox" id="term-privacy" value="I acknowledge" style="accent-color: #003631"> I acknowledge that I have received and read the <strong>Privacy Policy</strong>.</label>
                  <p style="color: red;" id="error-privacy">agree to privacy</p>
                  <label style="display:flex; gap:8px; align-items:flex-start; margin-top:8px;"><input type="checkbox" value="consent" style="accent-color: #003631"> I consent to receive marketing communications from Evergreen Bank about products and services that may interest me.</label>
                </div>
              </div>
            </div>

        </div>
          <div class="btn-container">
            <button class="button-action" id="button-prev" style="display: none;">Previous</button>
            <button class="button-action" id="button-next">Next</button>
          </div>
      </div>
    </main>

    <!-- Review Modal - Modern Design -->
    <div class="modal-container" style="display: none;">
      <div class="details-review">
        <!-- Modal Header -->
        <div class="modal-header">
          <h2>📋 Review Your Application</h2>
          <p>Please verify all information is correct before submitting</p>
        </div>

        <div class="details-contains">
          <!-- Personal Information Section -->
          <div class="review-section">
            <div class="section-header">
              <div class="section-icon personal">👤</div>
              <h3>Personal Information</h3>
            </div>
            <div class="review-grid">
              <div class="review-item">
                <label>First Name</label>
                <p class="value rev-f-name">—</p>
              </div>
              <div class="review-item">
                <label>Last Name</label>
                <p class="value rev-l-name">—</p>
              </div>
              <div class="review-item">
                <label>Date of Birth</label>
                <p class="value rev-birth">—</p>
              </div>
              <div class="review-item">
                <label>Email Address</label>
                <p class="value rev-email">—</p>
              </div>
              <div class="review-item full-width">
                <label>Phone Number</label>
                <p class="value rev-phone">—</p>
              </div>
            </div>
          </div>

          <!-- Address Section -->
          <div class="review-section">
            <div class="section-header">
              <div class="section-icon address">📍</div>
              <h3>Address Details</h3>
            </div>
            <div class="review-grid single-col">
              <div class="review-item">
                <label>Street Address</label>
                <p class="value rev-street">—</p>
              </div>
              <div class="review-item">
                <label>Location</label>
                <div class="location-badges">
                  <span class="location-badge">
                    <span class="badge-label">Brgy:</span>
                    <span class="badge-value rev-barangay">—</span>
                  </span>
                  <span class="location-badge">
                    <span class="badge-label">City:</span>
                    <span class="badge-value rev-city">—</span>
                  </span>
                </div>
              </div>
              <div class="review-item">
                <label>Region & Province</label>
                <div class="location-badges">
                  <span class="location-badge">
                    <span class="badge-value rev-region">—</span>
                  </span>
                  <span class="location-badge">
                    <span class="badge-value rev-state">—</span>
                  </span>
                  <span class="location-badge">
                    <span class="badge-label">ZIP:</span>
                    <span class="badge-value rev-zip">—</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Identity Verification Section -->
          <div class="review-section">
            <div class="section-header">
              <div class="section-icon identity">🔐</div>
              <h3>Identity Verification</h3>
            </div>
            <div class="review-grid">
              <div class="review-item full-width">
                <label>Social Security Number</label>
                <p class="value highlight rev-ssn">•••-••-••••</p>
              </div>
              <div class="review-item">
                <label>ID Type</label>
                <p class="value rev-id-type">—</p>
              </div>
              <div class="review-item">
                <label>ID Number</label>
                <p class="value rev-id-number">—</p>
              </div>
            </div>
          </div>

          <!-- Employment Section -->
          <div class="review-section">
            <div class="section-header">
              <div class="section-icon employment">💼</div>
              <h3>Employment Details</h3>
            </div>
            <div class="review-grid">
              <div class="review-item full-width">
                <label>Employment Status</label>
                <p class="value rev-employment-status">—</p>
              </div>
              <div class="review-item">
                <label>Employer Name</label>
                <p class="value rev-employer-name">—</p>
              </div>
              <div class="review-item">
                <label>Job Title</label>
                <p class="value rev-job-title">—</p>
              </div>
              <div class="review-item full-width">
                <label>Annual Income</label>
                <p class="value highlight rev-annual-income">—</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
          <p class="disclaimer">By clicking "Submit Application", you confirm that all information provided is accurate and complete.</p>
          <div class="btn-group">
            <button id="cancel">← Go Back</button>
            <button id="ok">Submit Application ✓</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Successful Modal - Modern Design -->
    <div class="modal-container" id="success-modal=container" style="display: none;">
      <div class="successful-modal">
        <div class="success-icon-wrap">
          <svg class="check" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#2e7d32" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="success-content">
          <h2 class="head-text">Application Submitted!</h2>
          <p class="sub-text">Your bank account application has been received. We'll review your information and contact you within 2-3 business days.</p>
        </div>
        <div class="s-wrap">
          <p class="grey-text"><strong>Reference Number:</strong> <span id="ref-id">EG-000000</span></p>
          <p class="grey-text"><strong>Submitted:</strong> <span id="date-submitted">—</span></p>
        </div>
        <button id="confirm-btn">Done</button>
      </div>
    </div>
  </body>
    <script>
    // panels
    let personalInfoPanel = document.querySelector(".personal-info-panel");
    let verificationPanel = document.querySelector(".verification-part");
    let reviewPanel = document.querySelector(".review-part");

    // buttons
    let prevBtn = document.getElementById("button-prev");
    let nextBtn = document.getElementById("button-next");

    // progress elements
    let formPartI = document.getElementById("form-part-I");
    let formPartII = document.getElementById("form-part-II");
    let formPartIII = document.getElementById("form-part-III");
    let lineI = document.getElementById("line-I");
    let lineII = document.getElementById("line-II");

    // modal elements
    let modalContainer = document.querySelector(".modal-container");
    let okBtn = document.getElementById("ok");
    let cancelBtn = document.getElementById("cancel");
    let successModal = document.getElementById("success-modal=container");
    let confirmBtn = document.getElementById("confirm-btn");

    // state
    let infoData = [];
    let step = 1;
    let acctType = null;

    // helper: show/hide field errors
    function showFieldError(inputEl, message) {
      inputEl.classList.add('input-error');
      let next = inputEl.nextElementSibling;
      if (!next || !next.classList || !next.classList.contains('field-error')) {
        let err = document.createElement('div');
        err.className = 'field-error';
        err.textContent = message;
        inputEl.insertAdjacentElement('afterend', err);
        err.style.display = 'block';
      } else {
        next.textContent = message;
        next.style.display = 'block';
      }
    }

    function clearFieldError(inputEl) {
      inputEl.classList.remove('input-error');
      let next = inputEl.nextElementSibling;
      if (next && next.classList && next.classList.contains('field-error')) {
        next.style.display = 'none';
      }
    }

    // Basic validators
    function isNotEmpty(val) { return val !== null && String(val).trim() !== ''; }
    function isEmail(val) { return /^\S+@\S+\.\S+$/.test(val); }
    function isNumeric(val) { return /^\d+(?:\.\d+)?$/.test(String(val).trim()); }
    function isSSN(val) { return /^(\d{3}-?\d{2}-?\d{4})$/.test(String(val).trim()); }

    // Age validation helper
    function isAtLeast18(dateString) {
      const birthDate = new Date(dateString);
      const today = new Date();
      
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      
      return age >= 18;
    }

    // Per-step validation
    function validatePersonal() {
      let valid = true;
      const required = ['f-name','l-name','e-mail','phone-number','date-of-birth','street-address','region','barangay','city','zip-code'];
      required.forEach(id => {
        const el = document.getElementById(id);
        if (!isNotEmpty(el.value)) {
          showFieldError(el, 'This field is required');
          valid = false;
        } else {
          clearFieldError(el);
        }
      });
      
      // Special validation for province (can be "NO_PROVINCE" for NCR)
      const provinceEl = document.getElementById('state');
      if (!isNotEmpty(provinceEl.value)) {
        showFieldError(provinceEl, 'This field is required');
        valid = false;
      } else {
        clearFieldError(provinceEl);
      }

      const emailEl = document.getElementById('e-mail');
      if (isNotEmpty(emailEl.value) && !isEmail(emailEl.value)) {
        showFieldError(emailEl, 'Enter a valid email address');
        valid = false;
      }

      // Validate age (must be 18+)
      const dobEl = document.getElementById('date-of-birth');
      if (isNotEmpty(dobEl.value)) {
        if (!isAtLeast18(dobEl.value)) {
          showFieldError(dobEl, 'You must be at least 18 years old to apply');
          valid = false;
        } else {
          clearFieldError(dobEl);
        }
      }

      return valid;
    }

    function validateVerification() {
      let valid = true;
      const ssnEl = document.getElementById('ssn');
      if (!isNotEmpty(ssnEl.value) || !isSSN(ssnEl.value)) {
        showFieldError(ssnEl, 'Enter a valid SSN (e.g. 123-45-6789)');
        valid = false;
      } else { clearFieldError(ssnEl); }

      const idNum = document.getElementById('id-number');
      if (!isNotEmpty(idNum.value)) { showFieldError(idNum, 'ID number required'); valid = false; } else { clearFieldError(idNum); }

      const employer = document.getElementById('employer-name');
      const job = document.getElementById('job-title');
      const income = document.getElementById('annual-income');
      const employment = document.getElementById('employment-status');

      if (!isNotEmpty(employment.value)) { showFieldError(employment, 'Select employment status'); valid = false; } else { clearFieldError(employment); }
      if (!isNotEmpty(employer.value)) { showFieldError(employer, 'Employer is required'); valid = false; } else { clearFieldError(employer); }
      if (!isNotEmpty(job.value)) { showFieldError(job, 'Job title required'); valid = false; } else { clearFieldError(job); }
      if (!isNotEmpty(income.value) || !isNumeric(income.value)) { showFieldError(income, 'Enter numeric income'); valid = false; } else { clearFieldError(income); }

      return valid;
    }

    function validateReview() {
      let valid = true;
      const tnc = document.getElementById('term-tnc');
      const privacy = document.getElementById('term-privacy');
      const errTnc = document.getElementById('error-tnc');
      const errPrivacy = document.getElementById('error-privacy');

      if (!tnc.checked) { errTnc.style.display = 'block'; valid = false; } else { errTnc.style.display = 'none'; }
      if (!privacy.checked) { errPrivacy.style.display = 'block'; valid = false; } else { errPrivacy.style.display = 'none'; }

      return valid;
    }

    // account type selection (toggle single selection)
    document.querySelector('.account-type-cards').addEventListener('click', function(e) {
      let card = e.target.closest('.acct-card');
      if (!card) return;
      document.querySelectorAll('.acct-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      acctType = card.id;
    });

    // Confetti animation
    function createConfetti() {
      const colors = ['#F1B24A', '#003631', '#1a6b62', '#ffd700'];
      for (let i = 0; i < 50; i++) {
        setTimeout(() => {
          const confetti = document.createElement('div');
          confetti.className = 'confetti';
          confetti.style.left = Math.random() * 100 + '%';
          confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
          confetti.style.animationDelay = Math.random() * 0.5 + 's';
          confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
          document.body.appendChild(confetti);
          
          setTimeout(() => confetti.remove(), 3000);
        }, i * 30);
      }
    }

    // Next/Prev handlers with validation
    prevBtn.addEventListener('click', function() {
      if (step > 1) step--;
      updateStepUI();
    });

    nextBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      if (step === 1) {
        if (!validatePersonal()) return;
        step++;
        updateStepUI();
        return;
      }
      
      if (step === 2) {
        if (!validateVerification()) return;
        step++;
        updateStepUI();
        return;
      }
      
      if (step === 3) {
        if (!validateReview()) return;
        
        // Add loading state to button
        nextBtn.classList.add('submitting');
        nextBtn.disabled = true;
        
        // Simulate processing time
        setTimeout(() => {
          // Gather data
          const provinceSelect = document.getElementById('state');
          const provinceValue = provinceSelect.value;
          const provinceName = provinceValue === 'METRO_MANILA' ? 'Metro Manila' : provinceSelect.options[provinceSelect.selectedIndex].text;
          
          let injector = {
            firstName: document.getElementById('f-name').value,
            lastName: document.getElementById('l-name').value,
            email: document.getElementById('e-mail').value,
            phoneNumber: document.getElementById('phone-number').value,
            dateOfBirth: document.getElementById('date-of-birth').value,
            streetAddress: document.getElementById('street-address').value,
            region: document.getElementById('region').options[document.getElementById('region').selectedIndex].text,
            barangay: document.getElementById('barangay').options[document.getElementById('barangay').selectedIndex].text,
            city: document.getElementById('city').options[document.getElementById('city').selectedIndex].text,
            state: provinceName,
            zipCode: document.getElementById('zip-code').value,
            socialSecurityNumber: document.getElementById('ssn').value,
            idType: document.getElementById('id-type').value,
            idNumber: document.getElementById('id-number').value,
            employmentStatus: document.getElementById('employment-status').value,
            employerName: document.getElementById('employer-name').value,
            jobTitle: document.getElementById('job-title').value,
            annualIncome: document.getElementById('annual-income').value,
            accountType: acctType,
          };
          infoData = [injector];
          
          // Populate review modal
          displayCred('rev-f-name', injector.firstName);
          displayCred('rev-l-name', injector.lastName);
          displayCred('rev-birth', formatDate(injector.dateOfBirth));
          displayCred('rev-email', injector.email);
          displayCred('rev-phone', injector.phoneNumber);
          displayCred('rev-street', injector.streetAddress);
          displayCred('rev-region', injector.region);
          displayCred('rev-state', injector.state);
          displayCred('rev-city', injector.city);
          displayCred('rev-barangay', injector.barangay);
          displayCred('rev-zip', injector.zipCode);
          displayCred('rev-ssn', maskSSN(injector.socialSecurityNumber));
          displayCred('rev-id-type', injector.idType);
          displayCred('rev-id-number', injector.idNumber);
          displayCred('rev-employment-status', injector.employmentStatus);
          displayCred('rev-employer-name', injector.employerName);
          displayCred('rev-job-title', injector.jobTitle);
          displayCred('rev-annual-income', formatCurrency(injector.annualIncome));

          // Remove loading state
          nextBtn.classList.remove('submitting');
          nextBtn.disabled = false;
          
          // Show modal with animation
          modalContainer.style.display = 'flex';
        }, 1500); // 1.5 second delay for effect
      }
    });

    okBtn.addEventListener('click', function() {
      // Disable button to prevent double submission
      okBtn.disabled = true;
      okBtn.textContent = 'Processing...';
      
      // Prepare data for submission - include codes for database storage
      const provinceCodeValue = document.getElementById('state').value;
      const applicationData = {
        ...infoData[0],
        // Include location codes for precise database storage
        regionCode: document.getElementById('region').value,
        provinceCode: provinceCodeValue === 'METRO_MANILA' ? 'NCR' : provinceCodeValue,
        cityCode: document.getElementById('city').value,
        barangayCode: document.getElementById('barangay').value,
        selectedCards: getSelectedCards(),
        additionalServices: getSelectedServices(),
        termsAccepted: document.getElementById('term-tnc').checked,
        privacyAcknowledged: document.getElementById('term-privacy').checked,
        marketingConsent: document.querySelectorAll('.terms-box input[type="checkbox"]')[2].checked
      };
      
      // Submit to backend
      fetch('submit_account_application.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(applicationData)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update reference ID with actual application number
          document.getElementById('ref-id').textContent = data.application_number;
          
          // Close review modal with animation
          modalContainer.classList.add('closing');
          
          setTimeout(() => {
            modalContainer.style.display = 'none';
            modalContainer.classList.remove('closing');
            
            // Show success modal
            successModal.style.display = 'flex';
            
            // Trigger confetti
            createConfetti();
          }, 300);
        } else {
          alert('Error: ' + data.message);
          okBtn.disabled = false;
          okBtn.textContent = 'Ok';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your application. Please try again.');
        okBtn.disabled = false;
        okBtn.textContent = 'Ok';
      });
    });

    cancelBtn.addEventListener('click', function() {
      modalContainer.classList.add('closing');
      setTimeout(() => {
        modalContainer.style.display = 'none';
        modalContainer.classList.remove('closing');
      }, 300);
    });

    confirmBtn.addEventListener('click', function() {
      successModal.classList.add('closing');
      setTimeout(() => {
        successModal.style.display = 'none';
        location.reload();
      }, 300);
    });

    // Display date submitted with better formatting
    let currentDate = new Date();
    let dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    let formattedDate = currentDate.toLocaleDateString('en-US', dateOptions);
    document.getElementById('date-submitted').textContent = formattedDate;
    
    // Helper function to get selected services
    function getSelectedServices() {
      const services = [];
      document.querySelectorAll('.services-grid input[type="checkbox"]:checked').forEach(cb => {
        services.push(cb.value);
      });
      return services;
    }

    // Format date to readable format
    function formatDate(dateString) {
      if (!dateString) return '—';
      const date = new Date(dateString);
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
    }

    // Mask SSN for display (show only last 4 digits)
    function maskSSN(ssn) {
      if (!ssn) return '•••-••-••••';
      const cleaned = ssn.replace(/\D/g, '');
      if (cleaned.length >= 4) {
        return '•••-••-' + cleaned.slice(-4);
      }
      return '•••-••-••••';
    }

    // Format currency
    function formatCurrency(amount) {
      if (!amount) return '—';
      const num = parseFloat(amount.replace(/[^0-9.-]+/g, ''));
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(num);
    }

    // Display credentials in review modal
    function displayCred(name, value) {
      let elements = document.getElementsByClassName(name);
      for (let i = 0; i < elements.length; i++) { elements[i].textContent = value || '—'; }
    }

    function updateStepUI() {
      personalInfoPanel.style.display = (step === 1) ? 'flex' : 'none';
      verificationPanel.style.display = (step === 2) ? 'flex' : 'none';
      reviewPanel.style.display = (step === 3) ? 'flex' : 'none';
      prevBtn.style.display = (step === 2 || step === 3) ? 'flex' : 'none';
      nextBtn.textContent = (step === 3) ? 'Submit' : 'Next';

      // Progress
      setCircle(formPartI, step >= 1);
      setCircle(formPartII, step >= 2);
      setCircle(formPartIII, step >= 3);

      // Progress lines with smooth transition
      lineI.style.transition = 'background-color 0.3s ease';
      lineII.style.transition = 'background-color 0.3s ease';
      lineI.style.backgroundColor = (step >= 2) ? '#003631' : '#E6E6E6';
      lineII.style.backgroundColor = (step >= 3) ? '#003631' : '#E6E6E6';
    }

    function setCircle(el, active) {
      if (!el) return;
      el.style.transition = 'all 0.3s ease';
      if (active) {
        el.classList.add('text-progress');
        el.classList.remove('progress');
        el.style.backgroundColor = '#003631';
        el.style.color = '#ffffff';
      } else {
        el.classList.remove('text-progress');
        el.classList.add('progress');
        el.style.backgroundColor = 'white';
        el.style.color = '#003631';
      }
    }

    // Initialize UI
    updateStepUI();

    // ========================================
    // PHILIPPINE LOCATION DROPDOWNS (Using PSGC Cloud API)
    // ========================================
    
    // Store current region code for later use
    let currentRegionCode = '';
    let regionHasNoProvinces = false;
    
    // Load regions on page load
    document.addEventListener('DOMContentLoaded', function() {
      loadRegions();
      setMaxBirthDate();
    });

    // Set max date for birth date (18 years ago from today)
    function setMaxBirthDate() {
      const today = new Date();
      const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
      const maxDateString = maxDate.toISOString().split('T')[0];
      document.getElementById('date-of-birth').setAttribute('max', maxDateString);
    }

    // Load all regions
    function loadRegions() {
      const regionSelect = document.getElementById('region');
      
      // Add loading indicator
      regionSelect.innerHTML = '<option value="">Loading regions...</option>';
      
      fetch('get_locations.php?action=get_regions')
        .then(response => response.json())
        .then(regions => {
          regionSelect.innerHTML = '<option value="">Select Region</option>';
          
          if (regions.length > 0) {
            regions.forEach(region => {
              const option = document.createElement('option');
              option.value = region.code;
              option.textContent = region.name;
              option.dataset.name = region.name;
              regionSelect.appendChild(option);
            });
          } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No regions available';
            regionSelect.appendChild(option);
          }
        })
        .catch(error => {
          console.error('Error loading regions:', error);
          regionSelect.innerHTML = '<option value="">Error loading regions</option>';
        });
    }

    // Load provinces when region is selected
    document.getElementById('region').addEventListener('change', function() {
      const regionCode = this.value;
      currentRegionCode = regionCode;
      const provinceSelect = document.getElementById('state');
      const citySelect = document.getElementById('city');
      const barangaySelect = document.getElementById('barangay');
      const zipCodeInput = document.getElementById('zip-code');
      
      // Clear and disable subsequent dropdowns
      provinceSelect.innerHTML = '<option value="">Select Province</option>';
      citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      zipCodeInput.value = '';
      
      citySelect.disabled = true;
      barangaySelect.disabled = true;
      regionHasNoProvinces = false;
      
      if (regionCode) {
        // Enable province dropdown and show loading
        provinceSelect.disabled = false;
        provinceSelect.innerHTML = '<option value="">Loading provinces...</option>';
        
        // Fetch provinces for selected region
        fetch(`get_locations.php?action=get_provinces&region_code=${encodeURIComponent(regionCode)}`)
          .then(response => response.json())
          .then(provinces => {
            if (provinces.length > 0) {
              // Region has provinces - normal flow
              regionHasNoProvinces = false;
              provinceSelect.innerHTML = '<option value="">Select Province</option>';
              provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.code;
                option.textContent = province.name;
                option.dataset.name = province.name;
                provinceSelect.appendChild(option);
              });
            } else {
              // Region has NO provinces (like NCR) - load cities directly from region
              regionHasNoProvinces = true;
              provinceSelect.innerHTML = '<option value="METRO_MANILA" data-name="Metro Manila">Metro Manila</option>';
              provinceSelect.value = 'METRO_MANILA';
              
              // Automatically load cities from region
              loadCitiesFromRegion(regionCode);
            }
          })
          .catch(error => {
            console.error('Error loading provinces:', error);
            provinceSelect.innerHTML = '<option value="">Error loading provinces</option>';
          });
      } else {
        provinceSelect.disabled = true;
      }
    });
    
    // Function to load cities directly from region (for regions without provinces)
    function loadCitiesFromRegion(regionCode) {
      const citySelect = document.getElementById('city');
      const barangaySelect = document.getElementById('barangay');
      const zipCodeInput = document.getElementById('zip-code');
      
      // Clear and enable city dropdown
      citySelect.innerHTML = '<option value="">Loading cities...</option>';
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      zipCodeInput.value = '';
      
      citySelect.disabled = false;
      barangaySelect.disabled = true;
      
      // Fetch cities directly from region
      fetch(`get_locations.php?action=get_cities&region_code=${encodeURIComponent(regionCode)}`)
        .then(response => response.json())
        .then(cities => {
          citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
          
          if (cities.length > 0) {
            cities.forEach(city => {
              const option = document.createElement('option');
              option.value = city.code;
              option.textContent = city.name;
              option.dataset.name = city.name;
              citySelect.appendChild(option);
            });
          } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No cities available';
            citySelect.appendChild(option);
          }
        })
        .catch(error => {
          console.error('Error loading cities:', error);
          citySelect.innerHTML = '<option value="">Error loading cities</option>';
        });
    }

    // Load cities when province is selected
    document.getElementById('state').addEventListener('change', function() {
      const provinceCode = this.value;
      const citySelect = document.getElementById('city');
      const barangaySelect = document.getElementById('barangay');
      const zipCodeInput = document.getElementById('zip-code');
      
      // If this is a region without provinces (NCR/Metro Manila), cities are already loaded
      if (provinceCode === 'METRO_MANILA') {
        return;
      }
      
      // Clear and disable subsequent dropdowns
      citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      zipCodeInput.value = '';
      
      barangaySelect.disabled = true;
      
      if (provinceCode) {
        // Enable city dropdown
        citySelect.disabled = false;
        citySelect.innerHTML = '<option value="">Loading cities...</option>';
        
        // Fetch cities for selected province
        fetch(`get_locations.php?action=get_cities&province_code=${encodeURIComponent(provinceCode)}`)
          .then(response => response.json())
          .then(cities => {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            
            if (cities.length > 0) {
              cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.code;
                option.textContent = city.name;
                option.dataset.name = city.name;
                citySelect.appendChild(option);
              });
            } else {
              const option = document.createElement('option');
              option.value = '';
              option.textContent = 'No cities available';
              citySelect.appendChild(option);
            }
          })
          .catch(error => {
            console.error('Error loading cities:', error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
          });
      } else {
        citySelect.disabled = true;
      }
    });

    // Load barangays when city is selected
    document.getElementById('city').addEventListener('change', function() {
      const cityCode = this.value;
      const barangaySelect = document.getElementById('barangay');
      const zipCodeInput = document.getElementById('zip-code');
      
      // Clear barangays and zip code
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      zipCodeInput.value = '';
      
      if (cityCode) {
        // Enable barangay dropdown
        barangaySelect.disabled = false;
        barangaySelect.innerHTML = '<option value="">Loading barangays...</option>';
        
        // Fetch barangays for selected city
        fetch(`get_locations.php?action=get_barangays&city_code=${encodeURIComponent(cityCode)}`)
          .then(response => response.json())
          .then(barangays => {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (barangays.length > 0) {
              barangays.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.code;
                option.textContent = barangay.name;
                option.dataset.name = barangay.name;
                barangaySelect.appendChild(option);
              });
            } else {
              const option = document.createElement('option');
              option.value = '';
              option.textContent = 'No barangays available';
              barangaySelect.appendChild(option);
            }
          })
          .catch(error => {
            console.error('Error loading barangays:', error);
            barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
          });
      } else {
        barangaySelect.disabled = true;
      }
    });

    // Auto-populate zip code when barangay is selected
    document.getElementById('barangay').addEventListener('change', function() {
      const barangaySelect = this;
      const citySelect = document.getElementById('city');
      const zipCodeInput = document.getElementById('zip-code');
      
      const barangayCode = barangaySelect.value;
      const barangayName = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
      const cityCode = citySelect.value;
      const cityName = citySelect.options[citySelect.selectedIndex]?.text || '';
      
      if (barangayCode && cityCode) {
        // Fetch zip code with both code and name for better lookup
        const params = new URLSearchParams({
          action: 'get_zipcode',
          city_code: cityCode,
          city_name: cityName,
          barangay_name: barangayName
        });
        
        fetch(`get_locations.php?${params.toString()}`)
          .then(response => response.json())
          .then(data => {
            if (data.zipcode) {
              zipCodeInput.value = data.zipcode;
            } else {
              zipCodeInput.value = '';
            }
          })
          .catch(error => {
            console.error('Error loading zip code:', error);
            zipCodeInput.value = '';
          });
      } else {
        zipCodeInput.value = '';
      }
    });

    // ========================================
    // CARD SELECTION
    // ========================================
    
    // Handle card selection checkboxes
    document.querySelectorAll('.card-option').forEach(option => {
      const checkbox = option.querySelector('.card-checkbox');
      
      // Click on card option to toggle checkbox
      option.addEventListener('click', function(e) {
        if (e.target !== checkbox) {
          checkbox.checked = !checkbox.checked;
          checkbox.dispatchEvent(new Event('change'));
        }
      });
      
      // Update visual state when checkbox changes
      checkbox.addEventListener('change', function() {
        if (this.checked) {
          option.classList.add('card-selected');
        } else {
          option.classList.remove('card-selected');
        }
      });
    });

    // Helper function to get selected cards
    function getSelectedCards() {
      const cards = [];
      document.querySelectorAll('.card-checkbox:checked').forEach(cb => {
        cards.push(cb.value);
      });
      return cards;
    }
</script>
</html>