<?php

/**
 * Knowband_Marketplace
 *
 * @category    Knowband
 * @package     Knowband_Marketplace
 * @author      Knowband Team <support@knowband.com>
 * @copyright   Knowband (http://wwww.knowband.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Knowband\Marketplace\Helper;

class Log extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    CONST FILESIZE = 5000000;
    CONST FILENAME = 'AuditLog';
    CONST FILEEXT = 'txt';
    CONST ZIPNAME = 'PreviousLog';
    CONST FOLDERNAME = 'Knowband_Marketplace';
    CONST INFOTYPENOTICE = 'Notice';
    CONST INFOTYPEERROR = 'Error';
    CONST INFOTYPEINFO = 'Info';
    CONST INFOTYPEWARNING = 'Warning';

    protected $mp_storeManager;
    protected $mp_scopeConfig;
    protected $mp_request;
    protected $mp_state;
    protected $inlineTranslation;
    protected $mp_transportBuilder;
    protected $rulesFactory;
    protected $mp_customerGroup;
    protected $mp_objectManager;
    protected $modelAuditLog;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Framework\App\Filesystem\DirectoryList $directorylist,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Backend\Model\Auth\Session $authSession
    )
    {
        $this->mp_storeManager = $storeManager;
        $this->mp_scopeConfig = $context->getScopeConfig();
        $this->mp_request = $context->getRequest();
        $this->mp_state = $state;
        $this->rulesFactory = $ruleFactory;
        $this->inlineTranslation = $inlineTranslation;
        $this->mp_transportBuilder = $transportBuilder;
        $this->mp_customerGroup = $customerGroup;   
        $this->mp_objectManager = $objectManager;
        $this->_blockFactory = $blockFactory;
        $this->_priceHelper = $priceHelper;
        $this->date = $date;
        $this->_store = $store;
        $this->assetRepo = $assetRepo;
        $this->_resource = $resource;
        $this->_directorylist = $directorylist;
        $this->_file = $file;
        $this->_cacheManager = $cacheManager;
        $this->authSession = $authSession;
        parent::__construct($context);
    }
    


    //Function to create the Log file (if not exist) and add the Log entry to the file.
    public function createFileAndWriteLogData($infoType, $functionName, $message)
    {
        $fileLocationDir = $this->_directorylist->getPath(\Magento\Framework\App\Filesystem\DirectoryList::LOG) . "/" . self::FOLDERNAME;

        //$file = new Varien_Io_File();

        $result = false;
        if (!is_dir($fileLocationDir)){
            $result = $this->_file->mkdir($fileLocationDir, 0775);
        }
        else{
            $result = true;
        }
        $urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $current_url = $urlInterface->getCurrentUrl();
        $filePath = $fileLocationDir . "/" . self::FILENAME . '.' . self::FILEEXT;
        //unset($file);
        $line = $this->convertChunksToString(
            array(
                $infoType,
                $this->date->date(),
                $functionName,
                $this->getAdminUserData(),
                $current_url,
                $message
            )   
        );
        if ($result) {
            $checkResult = $this->checkSizeAndRenameFile($filePath, $fileLocationDir);
            $f = fopen($filePath, 'a');

            fwrite($f, $line);

            fclose($f);
//            $this->auditLogEntryToDb($message, $functionName, $this->getAdminUserData());
            $this->_cacheManager->flush(array('config'));
        }
    }

    //If the log file size reaches more than 5000000 bytes it is renamed and Zipped through this function
    public function checkSizeAndRenameFile($filename, $fileLocationDir)
    {
        if (!is_file($filename))
            return true;
        else {
            $fileSize = filesize($filename);
            if ($fileSize > self::FILESIZE) {
                $zip = new ZipArchive();
                $filename = $fileLocationDir . '/' . self::ZIPNAME . '(' . $this->date->date() . ').zip';

                if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
                    return false;
                }
                $zip->addFile($fileLocationDir . '/' . self::FILENAME . '.' . self::FILEEXT, self::FILENAME . '.' . self::FILEEXT);

                $zip->close();
                unlink($fileLocationDir . '/' . self::FILENAME . '.' . self::FILEEXT);
                return true;
            }
        }
    }

    //Function for converting chunks (or array) to a string separated by \t so that it can be entered in a single line in the log file.
    public function convertChunksToString($chunks)
    {
        $str = '';
        foreach ($chunks as $val) {
            $str .= $val . "\t";
        }
        return (string) $str . PHP_EOL;
    }
    
    //Function for getting the details of the current User for Magento store.
    public function getAdminUserData()
    {
        $user = $this->authSession->getUser();
        if (isset($user)) {
            $userFirstname = $user->getFirstname();
            $userLastname = $user->getLastname();  
            $userUsername = $user->getUsername();
            return $userFirstname.' '.$userLastname.' ('.$userUsername.')';
        } else {
            return 'Front';
        }
    }
}
