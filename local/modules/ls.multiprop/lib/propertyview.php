<?
namespace Ls\Multiprop;

class Propertyview 
{

	protected static $usedFields = array('S', 'L', 'D', 'DT');

	/**
	* return array(array(id, name, type, values))
	* form array properties for iblock
	* for enum props values contain values :)
	* otherwise values undefined 
	*/
	public static function getProperties($iblock)
	{
		if(!\Bitrix\Main\Loader::IncludeModule('iblock'))
			throw new \Exception('module iblock not found');
			
		$res = \Bitrix\Iblock\PropertyTable::getList(array('filter' => array('IBLOCK_ID' => $iblock)));
		$propList = array();
		while($property = $res->fetch())
		{
			if($property['USER_TYPE'] == 'Date')
				$property['PROPERTY_TYPE'] = 'D';
			if($property['USER_TYPE'] == 'DateTime')
				$property['PROPERTY_TYPE'] = 'DT';
			$propList[$property['ID']] = array('id' => $property['ID'], 'name' => $property['NAME'], 'type' => $property['PROPERTY_TYPE'],  'rows' => $property['ROW_COUNT'], 'm' => $property['MULTIPLE']);
			if($property['PROPERTY_TYPE'] == 'L' && !$property['LINK_IBLOCK_ID'])
			{
				$res2 = \Bitrix\Iblock\PropertyEnumerationTable::getList(array('filter' => array('PROPERTY_ID' => $property['ID'])));
				while($enum = $res2->fetch())
					$propList[$property['ID']]['values'][] = $enum;
			}
		}

		return $propList;
	}
	
	
	
	public static function setPropertyValues($elementIdList, $iblockId, $propertyId, $value = false, $action = 'value')
	{
		if(
			!isset($elementIdList) 
			|| empty($elementIdList)
			|| !isset($propertyId) 
			|| empty($propertyId)
		)
			return false;
		if(!\Bitrix\Main\Loader::IncludeModule('iblock'))
			throw new \Exception('module iblock not found');
		
		
		if(!is_array($elementIdList) && $action && $action != 'value')
		{
			if(is_array($value))
				$value = $value[0];
				
			$newValue = false; 
			$oldValue = array();
			$res = \CIBlockElement::GetProperty($iblockId, $elementIdList, array(), array('ID' => $propertyId));
			while($row = $res->Fetch())
				$oldValue[] = $row['VALUE'];
			if(count($oldValue) < 1)
				$oldValue = '';
			elseif(count($oldValue) < 2)
				$oldValue = $oldValue[0];
				
			if(is_array($oldValue))
			{
				$newValue = array();
				foreach($oldValue as $oneOldValue)
					$newValue[] = static::applyValueAction($action, $value, $oneOldValue);
			}
			else
				$newValue = static::applyValueAction($action, $value, $oldValue);
		}
		$val = array($propertyId => ($newValue) ? $newValue : $value);
		if(is_array($elementIdList))
		{
			foreach($elementIdList as $id)	
				//\CIBlockElement::SetPropertyValuesEx($id, $iblockId, $val);
				static::setPropertyValues($id, $iblockId, $propertyId, $value);
		}
		else
			\CIBlockElement::SetPropertyValuesEx($elementIdList, $iblockId, $val);
		
		return true;
	}
	
	
	
	public static function checkIblockPage()
	{
		if(
			(
				$GLOBALS['APPLICATION']->GetCurPage() != '/bitrix/admin/iblock_element_admin.php'
				&& $GLOBALS['APPLICATION']->GetCurPage() != '/bitrix/admin/iblock_list_admin.php'
				&& $GLOBALS['APPLICATION']->GetCurPage() != '/bitrix/admin/cat_product_list.php'
			)
			|| intval($_REQUEST['IBLOCK_ID']) < 1
		)
		    return false;
		    
		return true;
	}
	
	
	public static function compileFilter()
	{
		$filter = array('IBLOCK_ID' => $_GET['IBLOCK_ID']);
		foreach($_GET as $k => $v)
		{
			if(!preg_match('/^find_([0-9a-z_]+)$/', $k))
				continue;
			
			if($k == 'find_id_1')
				$filter['<ID'] = $v;
			elseif($k == 'find_id_2')
				$filter['>ID'] = $v;
			elseif($k == 'find_section_section')
				$filter['IBLOCK_SECTION_ID'] = $v;
			else
				$filter[toUpper(substr($k, 5))] = $v;					
		}
		
		return $filter;
	}
	
	
	public static function getValueActions()
	{
		return array('value' => 'Заменить', 'math' => 'Математическая операция', 'concat' => 'Добавить');
	}
	
	public static function applyValueAction($action, $value, $oldValue = '')
	{
		print '<pre>params--';
		$newValue = false;
		
		switch($action)
		{
			case 'value':
				$newValue = $value;
			case 'concat':
				$newValue = $oldValue . $value;
			case 'math':
				$oldValue = floatval(str_replace(',', '.', $oldValue));
				$value = str_replace('(x)', $oldValue, $value);
				var_dump($value);
				$newValue = '$newValue=' . preg_replace('/[^\*\/\-\+\(\)\.%0-9]+/', '', $value) . ';';
				var_dump($newValue);
				eval($newValue);
				var_dump($newValue);
				break;
			default:
				$newValue = $value;

		}
		print '--params</pre>';
		return $newValue;
	}
	
	
	public static function OnPrologHandler()
	{
		//CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ID, "element_edit");
		
		
		if(!static::checkIblockPage() || !check_bitrix_sessid() || $_POST['action'] != 'multiset_prop' || !intval($_REQUEST['IBLOCK_ID']))
			return true;	
		
		if(!\Bitrix\Main\Loader::IncludeModule('iblock'))
			throw new \Exception('module iblock not found');
			
		$iblock = intval($_REQUEST['IBLOCK_ID']);	
		$_POST['multiset_prop_value'] = json_decode($_POST['multiset_prop_value'], true);		
		// ID can be empty, if checked "all"
		if($_POST['ID'])
		{
			// id has prefix, E - element, S - section
			// delete prefix and no-element items
			foreach($_POST['ID'] as $k => $id)
			{
				if(strpos($id, 'E') === false)
					unset($_POST['ID'][$k]);	
				$_POST['ID'][$k] = substr($id, 1);	
			}
			
			foreach($_POST['ID'] as $k => $id)
				static::setPropertyValues($id, $iblock, $_POST['multiset_prop_list'], $_POST['multiset_prop_value'], $_POST['multiset_prop_operation']);
		}
		// if we hasnt ID, we must apply filter to get items
		elseif($_POST['action_target'] == 'selected')
		{
			$filter = static::compileFilter();
			// filter can include properties/prices therefore we dont use d7
			$res = CIBlockElement::GetList(array(), $filter, false, false, array('ID'));
			while($row = $res->Fetch())
				static::setPropertyValues($row['ID'], $iblock, $_POST['multiset_prop_list'], $_POST['multiset_prop_value'], $_POST['multiset_prop_operation']);
		}
			
	}
	
	
	public static function OnAdminListDisplayHandler(&$list)
	{
		if(!static::checkIblockPage())
			return true;
			
		\CJSCore::Init(array("date"));
		    
		$iblock = intval($_REQUEST['IBLOCK_ID']);
		$propListHtml = '';
		$propListHtmlValues = '';
		$properties = static::getProperties($iblock);
		foreach($properties as $property)
		{
			if(!in_array($property['type'], static::$usedFields))
				continue;
			$propListHtml .= '<option value="' . $property['id'] . '">' . $property['name'] . '</option>';
		}
		$propListHtml = '<select id="multiset_prop_list" name="multiset_prop_list" onChange="createPropertyInput(this.value);"><option>-----</option>' . $propListHtml . '</select>';
		$propListHtml .= '<br><select id="multiset_prop_operation">';
		foreach(static::getValueActions() as $actionId => $actionName)
			$propListHtml .= '<option value="' . $actionId . '">' . $actionName . '</option>';
		$propListHtml .= '</select>';

		$propListHtmlValues = '<script>var propList = ' . \CUtil::PhpToJsObject($properties) . '; 
			function createPropertyInput(propertyId) 
			{
				document.getElementsByName(\'multiset_prop_list\')[0].value = propertyId;
				if(!propList[propertyId]) 
					return false; 
				var valueNode = document.getElementById(\'multiset_prop_value\'); 
				var html = \'\';
				if(propList[propertyId].type == \'S\' && propList[propertyId].rows == 1)
					html = \'<input type="text" name="multiset_prop_value1" class="fieldValueNode">\';
				if(propList[propertyId].type == \'S\' && propList[propertyId].rows > 1)
					html = \'<textarea name="multiset_prop_value1" class="fieldValueNode"></textarea>\';
				if(propList[propertyId].type == \'D\')
					html = \'<input type="text" name="multiset_prop_value1" class="fieldValueNode" onclick="BX.calendar({node: this, field: this, bTime: false,  bHideTime: true});">\';
				if(propList[propertyId].type == \'DT\')
					html = \'<input type="text" name="multiset_prop_value1" class="fieldValueNode" onclick="BX.calendar({node: this, field: this, bTime: true,  bHideTime: false});">\';
				if(propList[propertyId].type == \'L\')
				{
					html = \'<select name="multiset_prop_value1" class="fieldValueNode><option value="">-----</option>\';
					for(var i = 0; i < propList[propertyId].values.length; i++)
						html += \'<option value="\' + propList[propertyId].values[i].XML_ID + \'">\' + propList[propertyId].values[i].VALUE + \'</option>\';
					html += \'</select>\';
				}
				if(propList[propertyId].m == \'Y\')
					html += \'<br><input type="button" class="adm-btn" onClick="var fieldNode = this.parentNode.getElementsByClassName(\\\'fieldValueNode\\\')[0];  this.parentNode.insertBefore(fieldNode.cloneNode(), this); this.parentNode.insertBefore(document.createElement(\\\'br\\\'), this);" value="Еще">\';
				valueNode.innerHTML = html;			
			}
			function createPropertySelector()
			{
				if(window.dialog)
					window.dialog.DIV.remove();
				window.dialog = new BX.CDialog({
					content: \'<div class="multiset_prop_wrap">' . $propListHtml . '<div id="multiset_prop_value"></div></div>\'
				});
				dialog.SetButtons({
					title: "Применить", id: "ant-3", name: "ant-3", action: sendMultipropData
				});
				dialog.Show();
				
			}
			function sendMultipropData()
			{
				var buttons = window.multipropFormBtn.getElementsByClassName(\'adm-btn\');
				var formId = window.multipropFormBtn.getAttribute(\'id\').replace(\'_footer\', \'\');
					
				var btnActive = false;
				var tval = []; 
				var fields = document.getElementsByName(\'multiset_prop_value1\'); 
				for(var i = 0; i < fields.length; i++) 
					tval.push(fields[i].value);
				document.getElementsByName(\'multiset_prop_value\')[0].value = JSON.stringify(tval);
				document.getElementsByName(\'multiset_prop_operation\')[0].value = document.getElementById(\'multiset_prop_operation\').value;
				
				if(!document.getElementsByName(\'multiset_prop_list\')[0].value)
				{
					alert(\'Выберите свойство!\');
					return false;
				}
				
				if(document.getElementById(\'form_\' + formId).querySelectorAll(\'[name="ID[]"]:checked,[name="action_target"]:checked\').length)
				{
					for(var i = 0; i < buttons.length; i++)
					{
						if(buttons[i].getAttribute(\'name\') == \'apply\')
						{
							buttons[i].click();
							BX.WindowManager.Get().Close();
							break;
						}
					}
				}
				else
					alert(\'Выберите элементы!\');
			}
			</script><style>.multiset_prop_wrap > * {width: 100%} #multiset_prop_value > * {width:100%;padding:0;}</style><input type="hidden" name="multiset_prop_list"><input type="hidden" name="multiset_prop_value"><input type="hidden" name="multiset_prop_operation" value="value">';

		$list->arActions['multiset_prop'] = 'Изменить свойство';  
		$list->arActions['multiset_prop_value'] = array('type' => 'html', 'value' => $propListHtmlValues);
		$list->arActionsParams['select_onchange'] .= 'if(this.value == \'multiset_prop\'){ createPropertySelector(); window.multipropFormBtn = this.parentNode.parentNode; }';  
		
		print '<pre>';
		print_r($_POST);
		print '</pre>';
	}
}

?>
