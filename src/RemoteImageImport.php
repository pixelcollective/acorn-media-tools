<?php

namespace TinyPixel\Acorn\MediaTools;

use function \wp_parse_url;
use function \wp_upload_dir;
use function \wp_insert_attachment;
use function \get_attached_file;
use function \update_post_meta;
use function \wp_update_post;

/**
 * This class handles downloading a remote image file and inserting it
 * into the WP Media Library.
 *
 */
class RemoteImage
{
    /**
     * Remote image URL.
     *
     * @var string
     */
    protected $url = '';

    /**
     * The attachment data.
     *
     * @var array
     */
    protected $attachmentData = [
        'title'       => '',
        'caption'     => '',
        'altText'     => '',
        'description' => '',
    ];

    /**
     * The attachment ID or false if none.
     *
     * @var int|bool
     */
    protected $attachmentId = false;

    /**
     * Supported download types
     *
     * @var array
     */
    protected $supportedMimeTypes = [
        'image/jpeg',
        'image/gif',
        'image/png',
        'image/x-icon',
    ];

    /**
     * Constructor.
     *
     * @param  string $url The URL for the remote image.
     * @param  array $attachment_data
     */
    public function __construct(Application $app)
    {
        $this->url = $this->formatUrl($url);
    }

    /**
     * Import
     *
     * @return void
     */
    public function import(string $url, array $attachmentData = []) : void
    {
        if (is_array($attachmentData) && $attachmentData) {
            $this->attachmentData = array_map('sanitizeTextField', $attachmentData);
        }
    }

    /**
     * Add a scheme, if missing, to a URL.
     *
     * @param  string $url The URL.
     * @return string The URL, with a scheme possibly prepended.
     */
    protected function formatUrl($url) : string
    {
        if ($this->hasValidScheme($url)) {
            return $url;
        }

        if ($this->doesStringStartWith($url, '//')) {
            return "http:{$url}";
        }

        return "http://{$url}";
    }

    /**
     * Does this URL have a valid scheme?
     *
     * @param  string $url The URL.
     * @return bool
     */
    protected function hasValidSchemecheme($url)
    {
        return $this->doesStringStartWith($url, 'https://') ||
            $this->doesStringStartWith($url, 'http://');
    }

    /**
     * Does this string start with this substring?
     *
     * @param  string $string    The string.
     * @param  string $substring The substring.
     *
     * @return bool
     */
    protected function doesStringStartWith(string $string, string $substring)
    {
        return 0 === strpos($string, $substring);
    }

    /**
     * Download a remote image and insert it into the WordPress Media Library as an attachment.
     *
     * @return bool|int The attachment ID, or false on failure.
     */
    public function download()
    {
        if (! $this->isUrlValid()) {
            return false;
        }

        // Download remote file and sideload it into the uploads directory.
        $fileAttributes = $this->sideload();

        if (! $fileAttributes) {
            return false;
        }

        // Insert the image as a new attachment.
        $this->insertAttachment($fileAttributes['file'], $fileAttributes['type']);

        if (! $this->attachmentId) {
            return false;
        }

        $this->updateMetadata();
        $this->updatePostData();
        $this->updateAltText();

        return $this->attachmentId;
    }

    /**
     * Is this URL valid?
     *
     * @uses   \wp_parse_url
     * @return bool
     */
    protected function isUrlValid()
    {
        $parsedUrl = \wp_parse_url($this->url);

        return $this->hasValidScheme($this->url) && $parsedUrl && isset($parsedUrl['host']);
    }

    /**
     * Sideload the remote image into the uploads directory.
     *
     * @uses   function \wp_handle_sideload
     * @uses   function \download_url
     * @see    wp-admin/includes/file.php
     *
     * @return array|bool Associative array of file attributes, or false on failure.
     */
    protected function sideload()
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $tempFile = downloadUrl($this->url, 10);

        if (hasWordPressError($tempFile)) {
            return false;
        }

        $mimeType = mime_content_type($tempFile);

        if (! $this->isSupportedMimeType($mimeType)) {
            return false;
        }

        $file = [
            'name'    => $this->getFilename($mime_type),
            'type'    => $mime_type,
            'error'   => 0,
            'size'    => filesize($temp_file),
            'tmpName' => $temp_file,
        ];

        $overrides = [
            'testForm'   => false,
            'testSize'   => true,
            'testUpload' => true,
        ];

        $fileAttributes = \wp_handle_sideload($file, $overrides);

        if ($this->hasSideloadError($fileAttributes)) {
            return false;
        }

        return $fileAttributes;
    }

    /**
     * Is this image MIME type supported by the WordPress Media Libarary?
     *
     * @param  string $mime_type The MIME type.
     *
     * @return bool
     */
    protected function isSupportedMimeType($mime_type)
    {
        return in_array($mimeType, $this->supportedTypes, true);
    }

    /**
     * Get filename for attachment, including extension.
     *
     * @param  string $mime_type The MIME type.
     *
     * @return string            The filename.
     */
    protected function getFilename(string $mimeType) : string
    {
        if (empty($this->attachmentData['title'])) {
            return basename($this->url);
        }

        $filename  = sanitizeTitleWithDashes($this->attachmentData['title']);
        $extension = $this->getExtensionFromMimeType($mimeType);

        return $filename . $extension;
    }

    /**
     * Get a file extension, including the preceding '.' from a file's MIME type.
     *
     * @param  string $mime_type The MIME type.
     * @return string The file extension or empty string if not found.
     */
    protected function getExtensionFromMimeType($mimeType)
    {
        $extensions = [
            'image/jpeg'   => '.jpg',
            'image/gif'    => '.gif',
            'image/png'    => '.png',
            'image/x-icon' => '.ico',
        ];

        return isset($extensions[$mimeType]) ? $extensions[$mimeType] : '';
    }

    /**
     * Did an error occur while sideloading the file?
     *
     * @param  array $file_attributes The file attribues, or array containing an 'error' key on failure.
     * @return bool
     */
    protected function hasSideloadError($fileAttributes)
    {
        return isset($fileAttributes['error']);
    }

    /**
     * Insert attachment into the WordPress Media Library.
     *
     * @uses   function \wp_upload_dir
     * @uses   function \wp_insert_attachment
     * @param  string $filePath The path to the media file.
     * @param  string $mime_type The MIME type of the media file.
     */
    protected function insertAttachment($filePath, $mimeType)
    {
        $uploadDir = \wp_upload_dir();

        $attachmentData = array(
            'guid'           => $uploadDir['url'] . '/' . basename($filePath),
            'post_mime_type' => $mimeType,
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filePath)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachmentId = \wp_insert_attachment($attachmentData, $filePath);

        if (! $attachmentId) {
            return;
        }

        $this->attachmentId = $attachmentId;
    }

    /**
     * Update attachment metadata.
     *
     * @uses function \wp_generate_attachment_metadata
     * @see  wp-admin/includes/image.php
     */
    protected function updateMetadata()
    {
        if (! $filePath = \get_attached_file($this->attachmentId)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Generate metadata for the attachment and update the database record.
        $attachData = \wp_generate_attachment_metadata($this->attachmentId, $filePath);
        \wp_update_attachment_metadata($this->attachmentId, $attachData);
    }

    /**
     * Update attachment title, caption and description.
     *
     * @uses function \wp_update_post
     */
    protected function updatePostData()
    {
        if (empty($this->attachmentData['title'])
        && empty($this->attachmentData['caption'])
        && empty($this->attachmentData['description'])) {
            return;
        }

        $data = ['ID' => $this->attachmentId];

        // Set image title (post title)
        if (! empty($this->attachmentData['title'])) {
            $data['post_title'] = $this->attachmentData['title'];
        }

        // Set image caption (post excerpt)
        if (! empty($this->attachmentData['caption'])) {
            $data['post_excerpt'] = $this->attachmentData['caption'];
        }

        // Set image description (post content)
        if (! empty($this->attachmentData['description'])) {
            $data['post_content'] = $this->attachmentData['description'];
        }

        \wp_update_post($data);
    }

    /**
     * Update attachment alt text.
     *
     * @uses function \update_post_meta
     */
    protected function updateAltText()
    {
        if (empty($this->attachmentData['alt_text'])
        && empty($this->attachmentData['title'])) {
            return;
        }

        // Use the alt text string provided, or the title as a fallback.
        $altText = ! empty($this->attachmentData['alt_text'])
            ? $this->attachmentData['alt_text']
            : $this->attachmentData['title'];

        \update_post_meta($this->attachmentId, '_wp_attachment_image_alt', $altText);
    }
}
