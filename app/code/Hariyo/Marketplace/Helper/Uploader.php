<?php

/**
 * Hariyo_Marketplace
 *
 * @category    Hariyo
 * @package     Hariyo_Marketplace
 * @author      Chet B. Sunar Team <shivasnr41@gmail.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Hariyo\Marketplace\Helper;
class Uploader extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $_uploading_type = null;
    private $_allowed_extension = null;
    private $_validateCallbacks = [];
    private $_allowRenameFiles = false;
    private $_enableFilesDispersion = false;
    private $_dispretionPath = null;
    private $_allowCreateFolders = true;
    private $_fileExists = false;
    private $_file = [];
    private $_result = [];

    CONST IMG_NO_SELECTION = 'no_selection';
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_objectManager = $objectManager;
        $this->_coreFileStorage = $coreFileStorage;
        $this->_coreFileStorageDb = $coreFileStorageDb;
        parent::__construct($context);
    }
    
    public function getNoImageText() {
        return self::IMG_NO_SELECTION;
    }

    public function setUploadingType($type) {
        $this->_uploading_type = $type;
    }

    public function setAllowedExtension($extensions = array()) {
        foreach ((array) $extensions as $extension) {
            $this->_allowed_extension[] = strtolower($extension);
        }
    }

    public function getImageTypes() {
        $media_attribs = [
            'image' => ['label' => __('Base Image'), 'field' => 'product[image]'],
            'small_image' => ['label' => __('Small Image'), 'field' => 'product[small_image]'],
            'thumbnail' => ['label' => __('Thumbnail'), 'field' => 'product[thumbnail]']
        ];

        return $media_attribs;
    }

    private function makeImageDataStructure($data) {
        $this->_file['name'] = $data['name'];
        $this->_file['type'] = $data['type'];
        $this->_file['tmp_name'] = $data['tmp_name'];
        $this->_file['error'] = $data['error'];
        $this->_file['size'] = $data['size'];
    }

    public function addValidateCallback($callbackObject, $callbackMethod) {
        $this->_validateCallbacks[] = [
            'object' => $callbackObject,
            'method' => $callbackMethod
        ];
        return $this;
    }

    public function setAllowRenameFiles($flag) {
        $this->_allowRenameFiles = $flag;
    }

    public function setFilesDispersion($flag) {
        $this->_enableFilesDispersion = $flag;
    }

    private function _afterSave($result) {
        if (empty($result['path']) || empty($result['file'])) {
            return false;
        }

        $this->_result['tmp_name'] = str_replace(DIRECTORY_SEPARATOR, "/", $this->_result['tmp_name']);
        $this->_result['path'] = str_replace(DIRECTORY_SEPARATOR, "/", $this->_result['path']);

        switch ($this->_uploading_type) {
            case 'image': {
                    $this->_result['url'] = $this->_objectManager->get('Magento\Catalog\Model\Product\Media\Config')->getTmpMediaUrl($this->_result['file']);
                    $this->_result['file'] = $this->_result['file'] . '.tmp';

                    if ($this->_coreFileStorage->isInternalStorage() || $this->skipDbProcessing()) {
                        return false;
                    }

                    /** @var $dbHelper Mage_Core_Helper_File_Storage_Database */
                    $this->_result['file'] = $this->_coreFileStorageDb->saveUploadedFile($result);
                }
            case 'samples': {
                    $tmpPath = $this->_objectManager->get('\Magento\Downloadable\Model\Sample')->getBaseTmpPath();
                    if (isset($result['file'])) {
                        $fullPath = rtrim($tmpPath, "/") . "/" . ltrim($result['file'], "/");
                        $this->_coreFileStorageDb->saveFile($fullPath);
                    }
                }
            case 'links': {
                    $tmpPath = $this->_objectManager->get('\Magento\Downloadable\Model\Sample')->getBaseTmpPath();
                    if (isset($result['file'])) {
                        $fullPath = rtrim($tmpPath, "/") . "/" . ltrim($result['file'], "/");
                        $this->_coreFileStorageDb->saveFile($fullPath);
                    }
                }
            case 'link_samples': {
                    $tmpPath = $this->_objectManager->get('\Magento\Downloadable\Model\Link')->getBaseSampleTmpPath();
                    if (isset($result['file'])) {
                        $fullPath = rtrim($tmpPath, "/") . "/" . ltrim($result['file'], "/");
                        $this->_coreFileStorageDb->saveFile($fullPath);
                    }
                }
        }
    }

    public function save($imagePostData, $destinationFolder, $newFileName = null) {
        $this->makeImageDataStructure($imagePostData);
        $this->fileExist();
        $this->_validateFile();

        if ($this->_allowCreateFolders) {
            $this->_createDestinationFolder($destinationFolder);
        }

        if (!is_writable($destinationFolder)) {
            throw new \Exception('Destination folder is not writable or does not exists.');
        }

        $this->_result = false;

        $destinationFile = $destinationFolder;
        $fileName = isset($newFileName) ? $newFileName : $this->_file['name'];
        $fileName = \Magento\MediaStorage\Model\File\Uploader::getCorrectFileName($fileName);
        if ($this->_enableFilesDispersion) {
            $fileName = strtolower($fileName);
            $this->_dispretionPath = \Magento\MediaStorage\Model\File\Uploader::getDispretionPath($fileName);
            $destinationFile.= $this->_dispretionPath;
            $this->_createDestinationFolder($destinationFile);
        }

        if ($this->_allowRenameFiles) {
            $fileName = \Magento\MediaStorage\Model\File\Uploader::getNewFileName($this->_addDirSeparator($destinationFile) . $fileName);
        }

        $destinationFile = $this->_addDirSeparator($destinationFile) . $fileName;

        $this->_result = $this->_moveFile($this->_file['tmp_name'], $destinationFile);
        if ($this->_result) {
            chmod($destinationFile, 0777);
            if ($this->_enableFilesDispersion) {
                $fileName = str_replace(DIRECTORY_SEPARATOR, '/', $this->_addDirSeparator($this->_dispretionPath)) . $fileName;
            }
            //$this->_uploadedFileName = $fileName;
            //$this->_uploadedFileDir = $destinationFolder;
            $this->_result = $this->_file;
            $this->_result['path'] = $destinationFolder;
            $this->_result['file'] = $fileName;

            $this->_afterSave($this->_result);
//		    
//		    $this->_result['tmp_name'] = str_replace(DS, "/", $this->_result['tmp_name']);
//			$this->_result['path'] = str_replace(DS, "/", $this->_result['path']);
//
//			$this->_result['url'] = Mage::getSingleton('catalog/product_media_config')->getTmpMediaUrl($this->_result['file']);
//			$this->_result['file'] = $this->_result['file'] . '.tmp';
        }

        return $this->_result;
    }

    private function _addDirSeparator($dir) {
        if (substr($dir, -1) != DIRECTORY_SEPARATOR) {
            $dir.= DIRECTORY_SEPARATOR;
        }
        return $dir;
    }

    private function fileExist() {
        if (!file_exists($this->_file['tmp_name'])) {
            $code = empty($this->_file['tmp_name']) ? \Magento\Framework\File\Uploader::TMP_NAME_EMPTY : 0;
            throw new \Exception('File was not uploaded.', $code);
        } else {
            $this->_fileExists = true;
        }
    }

    private function _createDestinationFolder($destinationFolder) {
        if (!$destinationFolder) {
            return $this;
        }

        if (substr($destinationFolder, -1) == DIRECTORY_SEPARATOR) {
            $destinationFolder = substr($destinationFolder, 0, -1);
        }

        if (!(@is_dir($destinationFolder) || @mkdir($destinationFolder, 0777, true))) {
            throw new \Exception("Unable to create directory '{$destinationFolder}'.");
        }
    }

    protected function _moveFile($tmpPath, $destPath) {
        return move_uploaded_file($tmpPath, $destPath);
    }

    private function _validateFile() {
        if ($this->_fileExists === false) {
            return;
        }

        //is file extension allowed
        if (!$this->checkAllowedExtension($this->getFileExtension())) {
            throw new \Exception('Disallowed file type.');
        }

        //run validate callbacks
        foreach ($this->_validateCallbacks as $params) {
            if (is_object($params['object']) && method_exists($params['object'], $params['method'])) {
                $method = $params['method'];
                $params['object']->{$method}($this->_file['tmp_name']);
            }
        }
        //$this->validateUploadFile($this->_file['tmp_name']);
    }

    private function getFileExtension() {
        return $this->_fileExists ? pathinfo($this->_file['name'], PATHINFO_EXTENSION) : '';
    }

    private function checkAllowedExtension($extension) {
        if (!is_array($this->_allowed_extension) || empty($this->_allowed_extension)) {
            return true;
        }

        return in_array(strtolower($extension), $this->_allowed_extension);
    }

//    private function validateUploadFile($filePath) {
//        if (!getimagesize($filePath)) {
//            Mage::throwException(__('Disallowed file type.'));
//        }
//
//        $_processor = new Varien_Image($filePath);
//        return $_processor->getMimeType() !== null;
//    }

}
