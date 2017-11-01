<?
namespace Ls\Externalimage;


class CacheFunc
{
	protected static $values = array();
	
	public static function load()
	{
		//static::$values = json_decode(file_get_contents(__DIR__ . '/../cache.php'), true);	
	}
	
	public static function save()
	{
		//file_put_contents(__DIR__ . '/../cache.php', json_encode(static::$values));
	}
	
	
	public static function set($name, $value)
	{
		if(static::$values[$name] == $value)
			return true;
		static::$values[$name] = $value;
		//static::save();
		return true;
	}
	
	public static function get($name)
	{
		return static::$values[$name];
	}
}

?>
