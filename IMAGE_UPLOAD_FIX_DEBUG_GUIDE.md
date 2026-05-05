# 🔴 Image Upload 404 Fix - Debug & Test Guide

## ✅ Changes Made

### 1. **axios.js** - Removed instance-level Content-Type
**Line 91-101** - Removed `'Content-Type': 'application/json'` from instance defaults
- ❌ **Problem**: Content-Type was set at instance level, causing axios' default `transformRequest` to stringify FormData BEFORE the request interceptor could process it
- ✅ **Solution**: Only set Content-Type per-request in interceptor for non-FormData requests

### 2. **axios.js** - Added FormData Detection Logging
**Line 106-121** - Enhanced request interceptor with debug output
- Shows when FormData is detected
- Logs all FormData entries with file details (name, type, size)
- Confirms Content-Type is deleted before sending

### 3. **AuditForm.jsx** - Added FormData Verification
**Line 460-477** - Enhanced handleImageUpload with detailed logging
- Verifies file is appended to FormData
- Shows file name, type, and size
- Confirms FormData object has the file

### 4. **AuditAnswerImageController.php** - Added Backend Logging
**Line 33-45** - Added comprehensive debug logging
- Logs request headers and content type
- Shows if backend receives the file
- Captures all request data for analysis

---

## 🧪 Testing Steps

### **Step 1: Enable Debug Mode**
1. Open browser DevTools (F12)
2. Open Console tab
3. This will show all debug logs

### **Step 2: Test Image Upload**
1. Navigate to audit form
2. Answer a question with "Yes"
3. Click file upload button
4. Select any image file (jpg, png, gif, pdf, etc.)
5. Observe the flow

### **Step 3: Check Frontend Console Logs**

You should see in order:
```
📤 FormData detected - browser will set multipart/form-data boundary automatically
FormData entries: [["proof_image", "File: filename.jpg"]]
✅ Content-Type deleted for FormData - browser will auto-set multipart/form-data
📸 FormData Debug: {
  questionId: X,
  answerId: Y,
  fileName: "filename.jpg",
  fileType: "image/jpeg",
  fileSize: 12345,
  formDataHasFile: true,
  formDataEntries: [["proof_image", "File: filename.jpg"]]
}
```

❌ **BAD SIGN**: If you see `formDataHasFile: false` or File is not in entries, there's a different issue.

### **Step 4: Check Backend Logs**
1. After upload attempt, check Laravel logs: `storage/logs/laravel.log`
2. Look for debug entry with key "Image upload request received"
3. Check these critical fields:
   ```php
   'has_file' => true,           // ✅ Must be TRUE
   'content_type' => 'multipart/form-data; boundary=...', // ✅ Must be multipart
   'file_size' => > 0,           // ✅ Must show actual size
   'file_name' => 'filename.jpg' // ✅ Must show file name
   ```

---

## 🚀 Expected Success Signs

### ✅ Frontend
- No JavaScript errors in console
- FormData debug shows file is present
- Request interceptor logs show FormData detected
- Network tab shows `Content-Type: multipart/form-data; boundary=...`

### ✅ Backend
- Log shows `'has_file' => true`
- Log shows correct `content_type` with multipart boundary
- Log shows actual `file_size` (not 0)
- Image validation passes
- No more 404 errors

### ✅ User Experience
- Upload progress displays
- Success message appears
- Image URL is returned
- Analysis simulation runs

---

## 🔴 Troubleshooting

### Issue: Still getting 404?
1. Check backend log for `'has_file' => false`
   - If FALSE: FormData is still being stringified (check if another interceptor is running)
   - If TRUE: Answer ID mismatch or permission issue

2. Check `file_size` in log
   - If 0: File object exists but is empty
   - If actual size: File is being received correctly

### Issue: Content-Type shows `application/json`?
- The fix didn't get deployed/reloaded
- Clear browser cache and reload (Ctrl+Shift+R)
- Rebuild frontend if applicable

### Issue: Network tab shows JSON instead of multipart?
- Backend fix wasn't applied
- Browser is caching old code
- Check that Content-Type is removed in interceptor

---

## 📝 Files Modified
1. ✅ `app/jsx from frontend/axios.js` - Removed Content-Type from instance, added FormData logging
2. ✅ `app/jsx from frontend/AuditForm.jsx` - Added FormData verification logging
3. ✅ `app/Http/Controllers/AuditAnswerImageController.php` - Added backend request logging

---

## 💡 Key Fix Explanation

**The Root Issue:**
```javascript
// ❌ OLD (WRONG)
const instance = axios.create({
    headers: {
        'Content-Type': 'application/json'  // Causes FormData to be stringified!
    }
});
```

**Why It Failed:**
1. `Content-Type: application/json` set at instance level
2. When request made with FormData, axios' default `transformRequest` sees JSON content type
3. Transformer stringifies FormData → `{"proof_image": {}}` (empty because Files don't serialize)
4. Request interceptor runs too late to fix it
5. Backend receives JSON string instead of multipart form data
6. Backend validation fails: "proof_image field required"
7. 404 error returned

**The Solution:**
```javascript
// ✅ NEW (CORRECT)
const instance = axios.create({
    headers: {
        // Content-Type removed - let request interceptor handle it per-request
    }
});

// In interceptor:
if (isFormData) {
    delete config.headers['Content-Type'];  // Browser auto-sets multipart/form-data
}
```

---

## 🎯 Next Steps After Fix Verified
1. Test with various file types (jpg, png, gif, pdf)
2. Test with different file sizes
3. Verify file uploads to correct storage location
4. Remove debug logging once confirmed working
5. Deploy to production
