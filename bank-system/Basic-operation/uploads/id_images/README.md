# ID Images Upload Directory

This directory stores uploaded valid ID images (front and back) for account applications.

**Format:** `id_front_{customer_id}_{timestamp}.{ext}` and `id_back_{customer_id}_{timestamp}.{ext}`

**Permissions:** Ensure this directory has write permissions (0777 in development, 0755 in production)

**Security:**

- Files are validated for type (JPG, PNG, GIF only)
- Files are validated for size (max 5MB)
- Only authenticated customers can upload
- File paths are stored in database for retrieval

**Maintenance:**

- Consider implementing automatic cleanup of old files
- Monitor directory size
- Implement file encryption for sensitive data in production
