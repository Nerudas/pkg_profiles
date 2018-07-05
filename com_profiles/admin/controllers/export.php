<?php
/**
 * @package    Profiles Component
 * @version    1.1.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;

class ProfilesControllerExport extends BaseController
{
	/**
	 * Method for export to excel file
	 *
	 * @return boolean
	 *
	 * @since 1.0.3
	 */
	public function excel()
	{
		$app     = Factory::getApplication();
		$ids     = $app->input->get('cid', array(), 'array');
		$model   = $this->getModel();
		$headers = $model->getCsvHeaders();
		// Change the state of the records.
		if (!$items = $model->getCsvItems($ids))
		{
			JError::raiseWarning(500, $model->getError());
			$this->setRedirect('index.php?option=com_profiles&view=profiles');

			return false;
		}


		// Get Csv
		$file = fopen('php://output', 'w');
		ob_start();
		fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
		fputcsv($file, $headers, ';');
		foreach ($items as $item)
		{
			fputcsv($file, $item, ';');
		}
		$csv = htmlspecialchars_decode(ob_get_clean(), ENT_NOQUOTES);

		// Set Headers
		$date     = new Date();
		$filename = '[' . $date->format('Ymd') . '] profiles_excel';
		$app->clearHeaders();
		$app->setHeader('Pragma', 'public');
		$app->setHeader('Expires', '0');
		$app->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0, private');
		$app->setHeader('Content-Type', 'application/octet-stream');
		$app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.csv";');
		$app->setHeader('Content-Transfer-Encoding', 'binary');
		$app->sendHeaders();

		// Get file
		$app->close($csv);
		ob_end_clean();

		return true;
	}

	/**
	 *
	 * Proxy for getModel.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config The array of possible config values. Optional.
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getModel($name = 'Export', $prefix = 'ProfilesModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

}