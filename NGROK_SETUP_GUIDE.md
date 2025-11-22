# Ngrok Setup Guide for XAMPP

## Step 1: Download and Install Ngrok

1. **Download ngrok**:
   - Go to: https://ngrok.com/download/windows
   - Download the Windows ZIP file
   - Extract the `ngrok.exe` file to a folder (e.g., `C:\ngrok`)

2. **Add ngrok to PATH** (Optional but recommended):
   - Press `Win + X` and select "System"
   - Click "Advanced system settings"
   - Click "Environment Variables"
   - Under "User variables", select "Path" and click "Edit"
   - Click "New" and add the path where you extracted ngrok (e.g., `C:\ngrok`)
   - Click "OK" to save

   OR

   - You can run ngrok from its folder directly

## Step 2: Get Your Authtoken

1. Go to: https://dashboard.ngrok.com/get-started/your-authtoken
2. Copy your authtoken (it looks like: `2abc123def456ghi789jkl012mno345pqr678stu901vwx234yz_1A2B3C4D5E6F7G8H9I0J`)

## Step 3: Configure Ngrok with Your Authtoken

Open PowerShell or Command Prompt and run:

```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN_HERE
```

Replace `YOUR_AUTH_TOKEN_HERE` with the token you copied from step 2.

## Step 4: Start XAMPP Apache Server

1. Open XAMPP Control Panel
2. Make sure **Apache** is running (the status should show "Running")
3. Your Apache is configured to run on port 80

## Step 5: Start Ngrok Tunnel

Open a new PowerShell or Command Prompt window and run:

```bash
ngrok http 80
```

If your Apache is running on a different port (like 8080), use that port instead:
```bash
ngrok http 8080
```

## Step 6: Access Your Site

After running ngrok, you'll see output like:
```
Forwarding   https://abc123.ngrok-free.app -> http://localhost:80
```

Use the `https://` URL to access your Evergreen application from anywhere!

## Useful Ngrok Commands

- **View ngrok dashboard**: Open http://127.0.0.1:4040 in your browser (shows all requests)
- **Stop ngrok**: Press `Ctrl + C` in the terminal
- **Run in background**: Use `start` command on Windows

## Troubleshooting

- **"ngrok not recognized"**: Make sure ngrok.exe is in your PATH or run it from its directory
- **"authtoken not found"**: Make sure you completed Step 3 first
- **"address already in use"**: Another ngrok instance might be running, close it first
- **Can't access the site**: Make sure Apache is running in XAMPP Control Panel


