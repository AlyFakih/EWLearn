<?php
class FileHandler {
    private $allowedExtensions;
    private $maxFileSize;
    private $uploadDirectory;
    
    /**
     * Constructor
     * 
     * @param array $allowedExtensions Array of allowed file extensions (e.g., ['pdf', 'doc', 'docx'])
     * @param int $maxFileSize Maximum file size in bytes (default 10MB)
     * @param string $uploadDirectory Base upload directory (will create subdirectories)
     */
    public function __construct($allowedExtensions = [], $maxFileSize = 10485760, $uploadDirectory = '../../../uploads/') {
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;
        $this->uploadDirectory = $uploadDirectory;
        
        // Ensure the upload directory exists
        if (!file_exists($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
    }
    
    /**
     * Handle file upload with security checks and validation
     * 
     * @param array $file $_FILES['field_name']
     * @param string $subdirectory Optional subdirectory within upload directory
     * @param string $newFilename Optional new filename (without extension)
     * @return array Result array with status, message, and file data if successful
     */
    public function uploadFile($file, $subdirectory = '', $newFilename = '') {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
            return $this->getErrorResponse($file['error'] ?? UPLOAD_ERR_NO_FILE);
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'File is too large. Maximum size is ' . $this->formatFileSize($this->maxFileSize),
                'error_code' => 'size_exceeded'
            ];
        }
        
        // Get file information
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validate file extension
        if (!empty($this->allowedExtensions) && !in_array($extension, $this->allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Allowed types: ' . implode(', ', $this->allowedExtensions),
                'error_code' => 'invalid_type'
            ];
        }

        // Content-based check on top of the extension check above - a
        // renamed non-image/executable file (e.g. shell.php saved as
        // resume.pdf) would otherwise sail through on filename alone.
        if (!$this->validateFileContent($file['tmp_name'], $extension)) {
            return [
                'success' => false,
                'message' => 'File content does not match its declared type.',
                'error_code' => 'content_mismatch'
            ];
        }

        // Make sure subdirectory ends with a slash
        if (!empty($subdirectory)) {
            $subdirectory = rtrim($subdirectory, '/') . '/';
            
            // Create subdirectory if it doesn't exist
            if (!file_exists($this->uploadDirectory . $subdirectory)) {
                mkdir($this->uploadDirectory . $subdirectory, 0755, true);
            }
        }
        
        // Create a safe filename
        if (empty($newFilename)) {
            // Use the original filename but sanitize it
            $filename = $this->sanitizeFilename($fileInfo['filename']);
            // Add a unique identifier to avoid overwriting
            $filename = $filename . '_' . uniqid();
        } else {
            $filename = $this->sanitizeFilename($newFilename);
        }
        
        // Assemble the full path
        $newFilePath = $this->uploadDirectory . $subdirectory . $filename . '.' . $extension;
        
        // Scan file for viruses (disabled for now, would require external library)
        // $virusCheck = $this->scanForViruses($file['tmp_name']);
        // if ($virusCheck !== true) {
        //     return [
        //         'success' => false,
        //         'message' => 'Security check failed: ' . $virusCheck,
        //         'error_code' => 'security_check'
        //     ];
        // }
        
        // Move the uploaded file to destination
        if (move_uploaded_file($file['tmp_name'], $newFilePath)) {
            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_path' => $subdirectory . $filename . '.' . $extension,
                'file_name' => $filename . '.' . $extension,
                'file_size' => $this->formatFileSize($file['size']),
                'file_type' => $file['type'],
                'extension' => $extension
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file',
                'error_code' => 'move_failed'
            ];
        }
    }
    
    /**
     * Delete a file
     * 
     * @param string $filePath Path relative to the upload directory
     * @return bool True if file was deleted successfully
     */
    public function deleteFile($filePath) {
        $fullPath = $this->uploadDirectory . $filePath;
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Check if file exists
     * 
     * @param string $filePath Path relative to the upload directory
     * @return bool True if file exists
     */
    public function fileExists($filePath) {
        return file_exists($this->uploadDirectory . $filePath) && is_file($this->uploadDirectory . $filePath);
    }
    
    /**
     * Get file URL for downloading
     * 
     * @param string $filePath Path relative to the upload directory
     * @return string File URL
     */
    public function getFileUrl($filePath) {
        // Convert directory path to URL format
        $baseUrl = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath($this->uploadDirectory));
        return $baseUrl . '/' . $filePath;
    }
    
    /**
     * Verify a file's actual content matches its declared extension.
     *
     * Images get a strict check via getimagesize() (same approach already
     * used for the team-member/event photo uploads elsewhere in this app):
     * it decodes real image data, so a non-image file with a faked
     * extension is rejected even though the filename check already passed.
     *
     * Non-image types here are a mix of office documents and archives
     * (pdf/doc/docx/xls/xlsx/ppt/pptx/zip/rar/txt) whose real content-sniffed
     * MIME type varies by server/libmagic version - docx/xlsx/pptx in
     * particular are just zip containers and often report as generic
     * application/zip. Rather than a brittle per-extension allow-list that
     * would false-positive-reject legitimate documents, this instead
     * denies anything that content-sniffs as executable/script content
     * regardless of what extension it claims to be - the actual attack this
     * guards against (e.g. a PHP shell renamed to resume.pdf).
     *
     * @param string $tmpPath Path to the uploaded temp file
     * @param string $extension Lowercased extension already validated above
     * @return bool True if the content is acceptable
     */
    private function validateFileContent($tmpPath, $extension) {
        $imageMimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        if (isset($imageMimeMap[$extension])) {
            $imageInfo = @getimagesize($tmpPath);
            return $imageInfo !== false && $imageInfo['mime'] === $imageMimeMap[$extension];
        }

        if (!function_exists('finfo_open')) {
            // No fileinfo extension available - fall back to the extension
            // check that already ran rather than hard-failing every upload.
            return true;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $dangerousMimes = [
            'text/x-php', 'application/x-httpd-php', 'application/x-php',
            'application/x-executable', 'application/x-dosexec', 'application/x-msdownload',
            'application/x-sh', 'text/x-shellscript', 'application/x-perl', 'text/x-python',
        ];

        return !in_array($actualMime, $dangerousMimes, true);
    }

    /**
     * Sanitize filename to make it safe for storage
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    private function sanitizeFilename($filename) {
        // Remove invalid characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Trim underscores from beginning and end
        $filename = trim($filename, '_');
        // If filename is empty, use a default
        if (empty($filename)) {
            $filename = 'file_' . date('YmdHis');
        }
        return $filename;
    }
    
    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get error message based on PHP upload error code
     * 
     * @param int $errorCode PHP upload error code
     * @return array Error response
     */
    private function getErrorResponse($errorCode) {
        $message = 'Unknown upload error';
        
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = 'File upload stopped by extension';
                break;
        }
        
        return [
            'success' => false,
            'message' => $message,
            'error_code' => 'upload_error_' . $errorCode
        ];
    }
}
?>
