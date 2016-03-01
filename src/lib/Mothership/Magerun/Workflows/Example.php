<?php

/**
 * This file is part of the Mothership GmbH code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mothership\Magerun\Workflows;

use Mothership\StateMachine\WorkflowAbstract;

/**
 * Class General.
 *
 * @category  Mothership
 *
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 *
 * @link      http://www.mothership.de/
 *
 *            Use this job for a general purpose
 *
 */
class Example extends WorkflowAbstract
{
    public function second_state()
    {
    }

    public function load_document()
    {
    }

    /**
     * If there is no image, throw an exception.
     *
     *
     * @throws \Exception
     */
    public function has_images()
    {
    }

    /**
     * If the download directory does not exist, then create it.
     */
    public function download_directory_exist()
    {
    }

    /**
     * Every product needs to have a media gallery.
     *
     * @return bool
     */
    public function product_has_media_gallery()
    {
        return (rand(0, 1) == 1) ? true : false;
    }

    /**
     * Create the media gallery.
     */
    public function create_media_gallery()
    {
    }

    /**
     * Get all images and set the pointer to the first item.
     */
    public function get_images()
    {
        //$this->_collection = end($this->_images->parse($this->_document));
        for ($i = 0; $i <= 100; ++$i) {
            $this->_collection[] = ['test'];
        }
        $this->_pointer = 0;
    }

    /**
     * Set the pointer to the current image.
     */
    public function process_images()
    {
    }

    /**
     * Check that the current image exist as a copy.
     *
     * @return bool
     */
    public function original_image_exist_as_copy()
    {
        return (rand(0, 1) == 1) ? true : false;
    }

    /**
     * The image also needs to have the same checksum and the original one.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function hash_equals_original()
    {
        return (rand(0, 1) == 1) ? true : false;
    }

    /**
     * Remove existing images.
     */
    public function remove_existing()
    {
    }

    /**
     * Download from the Intex FTP.
     *
     *
     * @throws \Exception
     */
    public function download_original()
    {
    }

    public function assign_image_straight()
    {
    }

    public function assign_image()
    {
    }

    public function has_more()
    {
        if ($this->_pointer + 1 == count($this->_collection)) {
            return false;
        }
        ++$this->_pointer;

        return true;
    }

    public function finish()
    {
    }
}
