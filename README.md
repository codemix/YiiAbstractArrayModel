YiiAbstractArrayModel
=====================

Work with PHP files in an Yii Active Record way.

Whenever there is a bunch of PHP array files that need to be maintained through a web interface, one could do CRUD operations by extending the AbstractArrayModel class.


##Usage

Supposing we need to manage the following config files:


<b>/path/to/config/</b>
<pre>
config1.php
config2.php
config3.php
old-config1.php
...
</pre>

<b>config1.php</b>
<pre>
 return array(
     'name' => 'site',
     'theme' => 'bootstrap',
     'components' => array(
         'bill' => array(
             'siteId' => 11,
             'packages' => array(
                 8001,   
                 10442
             ),  
         ),
     ),
     'params' => array(
         'mainCssUrl' => '/css/app/site.css',
         'prefix' => 'M',
     )   
 );  
</pre>



We define our model by extending the AbstractArrayModel class and overriding the following methods:
<pre>
class Config extends AbstractArrayModel
{

    /**
     * Base path definition
     * @return string base path
     */
    public function getBasePath(){
        return '/path/to/config/';
    }

    /**
     * File pattern matching
     * @return string pattern
     */
    public function getPattern(){
        return '*.php';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('name, theme, components, params', 'safe'),
        );
    }   

    /**
     * Returns the list of all attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeNames()
    {
        return array('name', 'theme', 'components', 'params');
    }
}
</pre>

Following methods are now available:
<pre>
$model = Config::model()->findByPk('config1');
$model->theme = 'foundation'; 
$model->components['bill']['siteId'] = 99; 
$model->save(); //save the file

$model->delete(); //remove the file

$models = Config::model()->findAll(); // returns all files as models

//We can also pattern match the name. Internally uses the PHP [glob] (http://php.net/manual/en/function.glob.php) method 
$model = Config::model()->findA('old-*');
$models = Config::model()->findAll('config*');

</pre>


