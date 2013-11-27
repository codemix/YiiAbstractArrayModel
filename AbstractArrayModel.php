<?php

/**
 * Handle files containing PHP arrays in using an Active Record syntax 
 * Example model1.php:
 * <?php 
 * array(
 *   'key1' => val1,
 *   'key2' => array(
 *      'key21'=>val21',
 *      'key22'=>val22'
 *   )
 * );
 * Then one can manipulate the object:
 *   $m1 = $model->find('model1.php');
 *   $arr = $model->findAll('*.php');
 *   $m1->attributes = array('whatever changes' => 123);
 *   $m1->theme = 'bambam';
 *   $m1->save();
 *   $m1->delete();
 *
 */  
class AbstractArrayModel extends CModel
{
    /**
     * @var string the parent directory
     */
    protected $_basePath;

    /**
     * @var pattern to match file names;
     */
    protected $_pattern;

    /**
     * @var string filename (used as primary key field)
     */
    public $_filename;

    /**
     * Sets the BasePath
     *
     * @param string $basePath
     *
     * @return $this the current object
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = $basePath;
        return $this;
    }
 
    /**
     * Gets the BasePath
     * @return string
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }
 
    /**
     * Sets the Filename
     *
     * @param string $filename
     *
     * @return $this the current object
     */
    public function setFilename($filename)
    {
        $this->_filename = $filename;
        return $this;
    }
 
    /**
     * Gets the Filename
     * @return string
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * Convenience method to search for a specific file
     * @param string $pk
     * @param string|null $path the base path to look in
     *
     * @return AbstractArrayModel|null
     */
    public function findByPk($pk, $path = null){
        return $this->query($pk, $path, false);
    }

    /**
     * Find a model matching a given pattern
     * @param string|null $pattern the pattern to match
     * @param string|null $path the base path to look in
     *
     * @return AbstractArrayModel|AbstractArrayModel[]|null
     */
    public function find($pattern = null, $path = null)
    {
        return $this->query($pattern, $path, false);
    }
 
    /**
     * Find all the models matching a given pattern
     * @param string|null $pattern the pattern to match against
     * @param string|null $path the base path to the models
     *
     * @return AbstractArrayModel|AbstractArrayModel[]|null
     */
    public function findAll($pattern = null, $path = null)
    {
        return $this->query($pattern, $path, true);
    }

        /**
     * Queries for matching array models
     * @param null $pattern
     * @param null $path
     * @param bool $all
     *
     * @return AbstractArrayModel|AbstractArrayModel[]|null
     */
    protected function query($pattern = null, $path = null, $all = true)
    {
        if ($pattern == null)
            $pattern = '*.php'; // @todo add getDefaultPattern() ?
        if ($path === null)
            $path = $this->getBasePath();
        $files = (glob($path.$pattern));
        if (!$all) {
            if (!isset($files[0]))
                return null;
            else
                return $this->loadRecord($files[0], $path);
        }
        else
            return $this->loadAllRecords($files, $path);
    }

    /**
     * Save the array model
     * @param bool $runValidation whether to run validation
     * @return bool true if save was successful
     */
    public function save($runValidation = true)
    {
        if ($runValidation && !$this->validate())
            return false;
 
        return $this->writeFile();
    }

     /**
     * Write the file
     * @return bool true if successful
     * @throws CException if the filename or base path are not set
     */
    protected function writeFile()
    {
        $filename = $this->getFilename();
        $basePath = $this->getBasePath();
 
        if (empty($filename) || empty($basePath))
            throw new CException('filename and base path must be set');
 
        file_put_contents($basePath.$filename, $this->toPHP());
        return true;
    }
 
    /**
     * Return a PHP representation of this model
     * @return string the php code
     */
    public function toPHP()
    {
        return "<?php\nreturn ".var_export($this->getAttributes(), true).";\n";
    }
 
    /**
     * Attempt to delete the model
     * @return bool true if successful
     * @throws CException
     */
    public function delete()
    {
        $filename = $this->getFilename();
        $basePath = $this->getBasePath();
 
        if (empty($filename) || empty($basePath))
            throw new CException('filename and base path must be set');
 
        return unlink($basePath.$filename);
    }

     /**
     * @inheritDoc
     */
    public function __get($name)
    {
        if ($this->hasAttribute($name))
            return $this->getAttribute($name);
        else
            return parent::__get($name);
    }
 
    /**
     * @inheritDoc
     */
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name))
            $this->setAttribute($name, $value);
        else
            parent::__set($name, $value);
    }

    /**
     * Determine whether the model has an attribute with the given name
     *
     * @param string $name the attribute name
     *
     * @return bool true if the attribute exists
     */
    public function hasAttribute($name)
    {
        return in_array($name, $this->attributeNames());
    }
 
    /**
     * Set an attribute with the given name
     * @param string $name the attribute name
     * @param mixed $value the attribute value
     */
    public function setAttribute($name, $value)
    {
        $this->setAttributes(array(
            $name => $value
        ), false);
    }
 
    /**
     * Gets the value of the attribute with the given name
     *
     * @param string $name the attribute name
     *
     * @return mixed|null the attribute value
     */
    public function getAttribute($name)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$name]) ? $attributes[$name] : null;
    }

    /**
     * Gets the static model instance
     * @param string|null $className the name of the class to load
     *
     * @return AbstractArrayModel
     */
    public static function model($className = null)
    {
        static $models;
        if ($className === null)
            $className = get_called_class();
        if ($models === null)
            $models = array();
 
        if (!isset($models[$className]))
            $models[$className] = new $className(null);
 
        return $models[$className];
    }

     
    /**
     * Load a record with the given filename in the given base path.
     *
     * @param string $filename the filename, excluding path
     * @param string $basePath the path to the folder the record is in
     *
     * @return AbstractArrayModel
     */
    public function loadRecord($filename, $basePath)
    {
        $model = new static('update'); /** @var AbstractArrayModel $model */
        $model->setBasePath($basePath);
        $filename = substr($filename, strlen($basePath)); 
        $model->setFilename($filename);
        $model->setAttributes(require($basePath.$filename));
        return $model;
    }
 
    /**
     * Load all the records with the given filenames in the given base path
     * @param string[] $filenames and array of filenames
     * @param string $basePath the path to the folder which contains the files
     *
     * @return AbstractArrayModel[]
     */
    public function loadAllRecords($filenames, $basePath)
    {
        $results = array();
        foreach($filenames as $filename)
            $results[] = $this->loadRecord($filename, $basePath);
        return $results;
    }

    /**
     * Abstract method definition
     *
     * @return array()
     */
    public function attributeNames(){
        return array();
    }
}



