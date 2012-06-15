<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * @package 	TestLink
 * @author 		Andreas Morsing
 * @copyright 	2007-2009, TestLink community 
 * @version    	CVS: $Id: tlAttachmentRepository.class.php,v 1.6.2.2 2010/12/10 11:05:07 franciscom Exp $
 * @link 		http://www.teamst.org/index.php
 *
 * @internal
 * 20101210 - franciscom - added trim on attachemnt title, because '' is used on template logic.
 * 20101208 - franciscom - BUGID 4085: Attachment Repository on Database - Can not get file after upload
 * 20100918 - franciscom - BUGID 1890 - storeFileInFSRepository() - contribution by kinow	
 * 20091220 - franciscom - new method copyAttachments()
 *
 */

/**
 * class store and load attachments
 * @package 	TestLink
 */
class tlAttachmentRepository extends tlObjectWithDB
{
	//the one and only attachment repository object
	private static $s_instance;

	/**
	 * @var int the type of the repository
	 */
	private $repositoryType;
	/**
	 * @var int the compression type for the attachments
	 */
	private $repositoryCompressionType;

	/**
	 * @var string the path to the repository if filesystem 
	 */
	protected $repositoryPath;
	
	/**
	 * @var array additional attachment configuration
	 */
	protected $attachmentCfg;


	/**
	 * class constructor
	 * 
	 * @param $db [ref] resource the database connection
	 */
	function __construct(&$db)
	{
		tlObjectWithDB::__construct($db);
    	$this->repositoryType = self::getType();
    	$this->repositoryCompressionType = self::getCompression();
		$this->repositoryPath = self::getPathToRepository();
		$this->attachmentCfg = config_get('attachments');
	}

    /**
     * Creates the one and only repository object
     * 
     * @param $db [ref] resource the database connection
     * @return tlAttachmenRepository
     */
    public static function create(&$db)
    {
        if (!isset(self::$s_instance))
		{
            $c = __CLASS__;
            self::$s_instance = new $c($db);
        }

        return self::$s_instance;
    }

    /**
     * Returns the type of the repository, like filesystem, database,...
     * 
     * @return integer the type of the repository 
     */
    public static function getType()
    {
    	return config_get('repositoryType');
    }
	/**
	 * returns the compression type of the repository
	 * 
	 * @return integer the compression type
	 */
	public static function getCompression()
    {
    	return config_get('repositoryCompressionType');
    }
    /**
     * returns the path to the repository
     * 
     * @return string path to the repository
     */
    public static function getPathToRepository()
    {
    	return config_get('repositoryPath');
    }
    
    
	/**
	* Inserts the information about an attachment into the db
	*
	* @param int $fkid the foreign key id (attachments.fk_id)
	* @param string $fktableName the tablename to which the $id refers to (attachments.fk_table)
	* @param string $title the title used for the attachment
	* @param array $fInfo should be $_FILES in most cases
	*
	* @return int returns true if the information was successfully stored, false else
	*
	**/
	public function insertAttachment($fkid,$fkTableName,$title,$fInfo)
	{
		$fName = isset($fInfo['name']) ? $fInfo['name'] : null;
		$fType = isset($fInfo['type']) ? $fInfo['type'] : '';
		$fSize = isset($fInfo['size']) ? $fInfo['size'] : 0;
		$fTmpName = isset($fInfo['tmp_name']) ? $fInfo['tmp_name'] : '';

		$fContents = null;

		$fExt = getFileExtension(isset($fInfo['name']) ? ($fInfo['name']) : '',"bin");
		$destFPath = null;
		$destFName = getUniqueFileName($fExt);

		if ($this->repositoryType == TL_REPOSITORY_TYPE_FS)
		{
		 	$destFPath = $this->buildRepositoryFilePath($destFName,$fkTableName,$fkid);
			$fileUploaded = $this->storeFileInFSRepository($fTmpName,$destFPath);
		}
		else
		{
			$fContents = $this->getFileContentsForDBRepository($fTmpName,$destFName);
			$fileUploaded = sizeof($fContents);
			if($fileUploaded)
			{
		    	@unlink($fTmpName);	
		    }	
		}

		if ($fileUploaded)
		{
			$attachment = new tlAttachment();
			$fileUploaded = ($attachment->create($fkid,$fkTableName,$fName,$destFPath,$fContents,$fType,$fSize,$title) >= tl::OK);
			if ($fileUploaded)
			{
				$fileUploaded = $attachment->writeToDb($this->db);
			}
			else
			{ 
				@unlink($destFPath);
			}
		}

		return $fileUploaded;
	}

	/**
	 * Builds the path for a given filename according to the tablename and id
	 *
	 * @param string $destFName the fileName
	 * @param string $tableName the tablename to which $id referes to (attachments.fk_table)
	 * @param int $id the foreign key id attachments.fk_id)
	 *
	 * @return string returns the full path for the file
	 **/
	public function buildRepositoryFilePath($destFName,$tableName,$id)
	{
		$destFPath = $this->buildRepositoryFolderFor($tableName,$id,true);
		$destFPath .= DIRECTORY_SEPARATOR.$destFName;

		return $destFPath;
	}

	/**
	 * Fetches the contents of a file for storing it into the DB-repository
	 *
	 * @param string $fTmpName the filename of the attachment
	 * @param string $destFName a unique file name for temporary usage
	 *
	 * @return string the contents of the attachment to be stored into the db
	 **/
	protected function getFileContentsForDBRepository($fTmpName,$destFName)
	{
		$tmpGZName = null;
		switch($this->repositoryCompressionType)
		{
			case TL_REPOSITORY_COMPRESSIONTYPE_NONE:
				break;
			case TL_REPOSITORY_COMPRESSIONTYPE_GZIP:
				//copy the file into a dummy file in the repository and gz it and
				//read the file contents from this new file
				$tmpGZName = $this->repositoryPath.DIRECTORY_SEPARATOR.$destFName.".gz";
				gzip_compress_file($fTmpName, $tmpGZName);
				$fTmpName = $tmpGZName;
				break;
		}
		$fContents = getFileContents($fTmpName);
		//delete the dummy file if present
		if (!is_null($tmpGZName))
			unlink($tmpGZName);

		return $fContents;
	}

	/**
	 * Stores a file into the FS-repository. 
	 * It checks if the given  tmp name is of an uploaded file. 
	 * If so, it moves the file from the temp dir to the upload destination using move_uploaded_file(). 
	 * Else it simply rename the file through rename function.
	 * This process is needed to allow use of this method when uploading attachments via XML-RPC API
	 * 
	 * @param string $fTmpName the filename
	 * @param string $destFPath [ref] the destination file name
	 *
	 * @return bool returns true if the file was uploaded, false else
	 *
	 * @internal revision
	 * 20100918 - francisco.mancardi@gruppotesi.com - BUGID 1890 - contribution by kinow
	 **/
	protected function storeFileInFSRepository($fTmpName,&$destFPath)
	{
		switch($this->repositoryCompressionType)
		{
			case TL_REPOSITORY_COMPRESSIONTYPE_NONE:
				if ( is_uploaded_file($fTmpName))
				{
					$fileUploaded = move_uploaded_file($fTmpName,$destFPath);
				} 
				else 
				{
					$fileUploaded = rename($fTmpName,$destFPath);
				}
				break;
				
			case TL_REPOSITORY_COMPRESSIONTYPE_GZIP:
				//add the gz extension and compress the file
				$destFPath .= ".gz";
				$fileUploaded = gzip_compress_file($fTmpName,$destFPath);
				break;
		}
		return $fileUploaded;
	}

	/**
	 * Builds the repository folder for the attachment
	 *
	 * @param string $tableName the tablename to which $id referes to (attachments.fk_table)
	 * @param int $id the foreign key id attachments.fk_id)
	 * @param bool $mkDir if true then the the directory will be created, else not
	 *
	 * @return string returns the full path for the folder
	 **/
 	protected function buildRepositoryFolderFor($tableName,$id,$mkDir = false)
	{
		$path = $this->repositoryPath.DIRECTORY_SEPARATOR.$tableName;
		if ($mkDir && !file_exists($path))
			mkdir($path);
		$path .= DIRECTORY_SEPARATOR.$id;
		if ($mkDir && !file_exists($path))
			mkdir($path);

		return $path;
	}

	/**
	 * Deletes an attachment from the filesystem
	 * 
	 * @param $dummy not used, only there to keep the interface equal to deleteAttachmentFromDB
	 * @param $attachmentInfo array with information about the attachments
	 * @return interger returns tl::OK on success, tl::ERROR else
	 */
	protected function deleteAttachmentFromFS($dummy,$attachmentInfo = null)
	{
		$filePath = $attachmentInfo['file_path'];

		$destFPath = $this->repositoryPath.DIRECTORY_SEPARATOR.$filePath;
		return @unlink($destFPath) ? tl::OK : tl::ERROR;
	}

	/**
	 * Deletes an attachment from the database
	 * 
	 * @param $id integer the database identifier of the attachment
	 * @param $dummy not used, only there to keep the interface equal to deleteAttachmentFromDB
	 * @return integer returns tl::OK on success, tl::ERROR else
	 */
	protected function deleteAttachmentFromDB($id,$dummy = null)
	{
		$attachment = new tlAttachment($id);
		return $attachment->deleteFromDB($this->db);
	}

	/**
	 * Deletes the attachment with the given database id
	 * 
	 * @param $id integer the database identifier of the attachment
	 * @param $attachmentInfo array, optional information about the attachment
	 * @return integer returns tl::OK on success, tl::ERROR else
	 */
	public function deleteAttachment($id,$attachmentInfo = null)
	{
		$bResult = tl::ERROR;
		if (is_null($attachmentInfo))
			$attachmentInfo = $this->getAttachmentInfo($id);
		if ($attachmentInfo)
		{
			$bResult = tl::OK;
			if (trim($attachmentInfo['file_path']) != "")
				$bResult = $this->deleteAttachmentFromFS($id,$attachmentInfo);
			$bResult = $this->deleteAttachmentFromDB($id,null) && $bResult;
		}
		return $bResult ? tl::OK : tl::ERROR;
	}
	
	/**
	 * Gets the contents of the attachments from the repository
	 * 
	 * @param $id integer the database identifier of the attachment
	 * @param $attachmentInfo array, optional information about the attachment
	 * @return string the contents of the attachment or null on error
	 *
	 * @internal revision
	 * 20101208 - franciscom - BUGID 4085
	 */
	public function getAttachmentContent($id,$attachmentInfo = null)
	{
		$content = null;
		if (!$attachmentInfo)
		{
			$attachmentInfo = $this->getAttachmentInfo($id);
		}
		
		if ($attachmentInfo)
		{
			$fname = 'getAttachmentContentFrom';
			$fname .= ($this->repositoryType == TL_REPOSITORY_TYPE_FS) ? 'FS' : 'DB';
			$content = $this->$fname($id);
		}
		return $content;
	}
	
	/**
	 * Gets the contents of the attachment given by it's database identifier from the filesystem 
	 * 
	 * @param $id integer the database identifier of the attachment
	 * @return string the contents of the attachment or null on error
	 */
	protected function getAttachmentContentFromFS($id)
	{
		$query = "SELECT file_size,compression_type,file_path " .
		         " FROM {$this->tables['attachments']} WHERE id = {$id}";
		$row = $this->db->fetchFirstRow($query);

		$content = null;
		if ($row)
		{
			$filePath = $row['file_path'];
			$fileSize = $row['file_size'];
			$destFPath = $this->repositoryPath.DIRECTORY_SEPARATOR.$filePath;
			switch($row['compression_type'])
			{
				case TL_REPOSITORY_COMPRESSIONTYPE_NONE:
					$content = getFileContents($destFPath);
					break;
					
				case TL_REPOSITORY_COMPRESSIONTYPE_GZIP:
					$content = gzip_readFileContent($destFPath,$fileSize);
					break;
			}
		}

		return $content;
	}
	/**
	 * Gets some common infos about attachments
	 *
	 * @param int $id the id of the attachment (attachments.id)
	 *
	 * @return string returns the contents of the attachment
	*/
	//@TODO schlundus, should be protected, but blocker is testcase::copy_attachments
	public function getAttachmentContentFromDB($id)
	{
		$query = "SELECT content,file_size,compression_type " .
		         " FROM {$this->tables['attachments']} WHERE id = {$id}";
		$row = $this->db->fetchFirstRow($query);

		$content = null;
		if ($row)
		{
			$content = $row['content'];
			$fileSize = $row['file_size'];
			switch($row['compression_type'])
			{
				case TL_REPOSITORY_COMPRESSIONTYPE_NONE:
					break;
					
				case TL_REPOSITORY_COMPRESSIONTYPE_GZIP:
					$content = gzip_uncompress_content($content,$fileSize);
					break;
			}
		}

		return $content;
	}
	
	/**
	 * Deletes all attachments of a certain object of a given type
	 * 
	 * @param $fkid integer the id of the object whose attachments should be deleted
	 * @param $fkTableName the "type" of the object, or the table the object is stored in 
	 * 
	 * @return boolean returns bSuccess if all attachments are deleted, false else
	 */
	public function deleteAttachmentsFor($fkid,$fkTableName)
	{
		$bSuccess = true;
		$attachmentIDs = $this->getAttachmentIDsFor($fkid,$fkTableName);
		for($i = 0;$i < sizeof($attachmentIDs);$i++)
		{
			$id = $attachmentIDs[$i];
			$bSuccess = ($this->deleteAttachment($id) && $bSuccess);
		}
		if ($bSuccess)
		{
			$folder = $this->buildRepositoryFolderFor($fkTableName,$fkid);
			if (is_dir($folder))
			{
				rmdir($folder);
			}
		}
		return $bSuccess;
	}

	/**
	 * Reads the information about the attachment with the given database id
	 * 
	 * @param $id integer the database identifier of the attachment
	 * @return array the information about the attachment
	 */
	public function getAttachmentInfo($id)
	{
		$info = null;
		$attachment = new tlAttachment($id);
		if ($attachment->readFromDB($this->db))
		{
			$info = $attachment->getInfo();
        }
		return $info;
	}
	
	/**
	 * Reads all attachments for a certain object of a given type
	 * 
	 * @param $fkid integer the id of the object whose attachments should be read
	 * @param $fkTableName the "type" of the object, or the table the object is stored in 
	 * 
	 * @return arrays returns an array with the attachments of the objects, or null on error
	 */
	public function getAttachmentInfosFor($fkid,$fkTableName)
	{
		$attachmentInfos = null;
		$attachmentIDs = $this->getAttachmentIDsFor($fkid,$fkTableName);
		$loop2do = sizeof($attachmentIDs);
		for($idx = 0;$idx < $loop2do; $idx++)
		{
			$attachmentInfo = $this->getAttachmentInfo($attachmentIDs[$idx]);
			if ($attachmentInfo)
			{
				// needed because on inc_attachments.tpl this test:
				// {if $info.title eq ""}
				// is used to undertand if icon or other handle is needed to access
				// file content
				$attachmentInfo['title'] = trim($attachmentInfo['title']);
				$attachmentInfos[] = $attachmentInfo;
			}
		}
		return $attachmentInfos;
	}
	
	/**
	 * Yields all attachmentids for a certain object of a given type
	 * 
	 * @param $fkid integer the id of the object whose attachments should be read
	 * @param $fkTableName the "type" of the object, or the table the object is stored in 
	 * 
	 * @return arrays returns an array with the attachments of the objects, or null on error
	 */
	public function getAttachmentIDsFor($fkid,$fkTableName)
	{
		$order_by = $this->attachmentCfg->order_by;

		$query = "SELECT id FROM {$this->tables['attachments']} WHERE fk_id = {$fkid} " .
		         " AND fk_table = '" . $this->db->prepare_string($fkTableName). "' " . $order_by;
		$attachmentIDs = $this->db->fetchColumnsIntoArray($query,'id');

		return $attachmentIDs;
	}

    /*
  	 * @param $fkTableName the "type" of the object, or the table the object is stored in 
     */
	function copyAttachments($source_id,$target_id,$fkTableName)
	{
		$f_parts = null;
		$destFPath = null;
		$mangled_fname = '';
		$status_ok = false;
		$repo_type = config_get('repositoryType');
		$repo_path = config_get('repositoryPath') .  DIRECTORY_SEPARATOR;
		
		$attachments = $this->getAttachmentInfosFor($source_id,$fkTableName);
		if(count($attachments) > 0)
		{
			foreach($attachments as $key => $value)
			{
				$file_contents = null;
				$f_parts = explode(DIRECTORY_SEPARATOR,$value['file_path']);
				$mangled_fname = $f_parts[count($f_parts)-1];
				
				if ($repo_type == TL_REPOSITORY_TYPE_FS)
				{
					$destFPath = $this->buildRepositoryFilePath($mangled_fname,$table_name,$target_id);
					$status_ok = copy($repo_path . $value['file_path'],$destFPath);
				}
				else
				{
					$file_contents = $this->getAttachmentContentFromDB($value['id']);
					$status_ok = sizeof($file_contents);
				}
				if($status_ok)
				{
		            $attachmentMgr = new tlAttachment();
					$attachmentMgr->create($target_id,$fkTableName,$value['file_name'],
						                   $destFPath,$file_contents,$value['file_type'],
						                   $value['file_size'],$value['title']);
					$attachmentMgr->writeToDB($this->db);
				}
			}
		}
	}


}
?>