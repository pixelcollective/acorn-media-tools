<?php

namespace TinyPixel\Acorn\MediaTools;

use function \download_url;
use function \is_wp_error;
use function \wp_generate_attachment_metadata;
use function \wp_handle_sideload;
use function \wp_parse_url;
use function \wp_update_attachment_metadata;
use function \wp_upload_dir;
use function \wp_insert_attachment;
use function \get_attached_file;
use function \update_post_meta;
use function \wp_update_post;
use function \sanitize_title_with_dashes;
use TinyPixel\Support\MimeTypes;
use Roots\Acorn\Application;

/**
 * Remote Image Import
 *
 * @author  Kelly Mears <kelly@tinypixel.dev>
 * @license MIT
 * @since   1.0.0
 * @version 1.0.0
 */
class RemoteImageImport
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
     * Remote upload overrides
     *
     * @var array
     */
    protected static $uploadOverrides = [
        'test_form'   => false,
        'test_size'   => true,
        'test_upload' => true,
    ];

    /**
     * Constructor.
     *
     * @param  string $url The URL for the remote image.
     * @param  array $attachment_data
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->mimeTypes = MimeTypes::make();

        return $this;
    }

    /**
     * Import
     *
     * @param string $url
     * @param array $attachmentData
     *
     * @return RemoteImageImport
     */
    public function import(string $url, array $attachmentData = []) : RemoteImageImport
    {
        if (is_array($attachmentData) && $attachmentData) {
            $this->attachmentData = array_map(
                'sanitize_text_field',
                $attachmentData
            );
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Download a remote image and insert it into the WordPress Media Library as an attachment.
     *
     * @return int The attachment ID (if successful)
     */
    public function download() : int
    {
        $attr = $this->sideload();

        $this->insertAttachment(
            $attr['file'],
            $attr['type']
        );

        $this->updateMetadata();

        $this->updatePostData();

        return $this->attachmentId;
    }

    /**
     * Sideload the remote image into the uploads directory.
     *
     * @uses   function \wp_handle_sideload
     * @uses   function \download_url
     * @uses   function \is_wp_error
     * @see    wp-admin/includes/file.php
     *
     * @return array|bool Associative array of file attributes, or false on failure.
     */
    protected function sideload()
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $tmpFile = download_url($this->url, 10);

        $fileAttributes = [
            'name'     => basename($this->url),
            'type'     => mime_content_type($tmpFile),
            'tmp_name' => $tmpFile,
            'error'    => 0,
            'size'     => filesize($tmpFile),
        ];

        return wp_handle_sideload(
            $fileAttributes,
            self::$uploadOverrides
        );
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
        $uploadDir = wp_upload_dir()['url'];
        $fileName  = basename($filePath);
        $postTitle = preg_replace('/\.[^.]+$/', '', $fileName);

        $this->attachmentId = \wp_insert_attachment([
            'guid'           => "{$uploadDir}/{$fileName}",
            'post_mime_type' => $mimeType,
            'post_title'     => $postTitle,
            'post_status'    => 'inherit',
            'post_content'   => '',
        ], $filePath);

        if ($this->attachmentId) {
            return $this->attachmentId;
        }

        return false;
    }

    /**
     * Update attachment metadata.
     *
     * @uses function \wp_generate_attachment_metadata
     * @see  wp-admin/includes/image.php
     */
    protected function updateMetadata()
    {
        if (!$filePath = \get_attached_file($this->attachmentId)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attach = \wp_generate_attachment_metadata(
            $this->attachmentId,
            $filePath
        );

        \wp_update_attachment_metadata($this->attachmentId, $attach);
    }

    /**
     * Update attachment title, caption and description.
     *
     * @uses function \wp_update_post
     */
    protected function updatePostData()
    {
        if (empty($this->attachmentData['title'])
        &&  empty($this->attachmentData['caption'])
        &&  empty($this->attachmentData['description'])) {
            return;
        }

        // Set image id
        $data = ['ID' => $this->attachmentId];

        // Set image title
        if (! empty($this->attachmentData['title'])) {
            $data['post_title'] = $this->attachmentData['title'];
        }

        // Set image caption
        if (! empty($this->attachmentData['caption'])) {
            $data['post_excerpt'] = $this->attachmentData['caption'];
        }

        // Set image description
        if (! empty($this->attachmentData['description'])) {
            $data['post_content'] = $this->attachmentData['description'];
        }

        // Set image alt text (default to title if empty)
        if (! empty($this->attachmentData['alt_text'])) {
            update_post_meta($this->attachmentId, '_wp_attachment_image_alt', $this->attachmentData['alt_text']);
        } else {
            update_post_meta($this->attachmentId, '_wp_attachment_image_alt', $this->attachmentData['title']);
        }

        // Insert post
        wp_update_post($data);
    }
}
