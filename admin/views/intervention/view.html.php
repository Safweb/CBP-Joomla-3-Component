<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.0.8
	@build			1st December, 2015
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Intervention View class
 */
class CostbenefitprojectionViewIntervention extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors')))
                {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Assign the variables
		$this->form 		= $this->get('Form');
		$this->item 		= $this->get('Item');
		$this->script 		= $this->get('Script');
		$this->state		= $this->get('State');
                // get action permissions
		$this->canDo		= CostbenefitprojectionHelper::getActions('intervention',$this->item);
		// get input
		$jinput = JFactory::getApplication()->input;
		$this->ref 		= $jinput->get('ref', 0, 'word');
		$this->refid            = $jinput->get('refid', 0, 'int');
		$this->referral         = '';
		if ($this->refid)
                {
                        // return to the item that refered to this item
                        $this->referral = '&ref='.(string)$this->ref.'&refid='.(int)$this->refid;
                }
                elseif($this->ref)
                {
                        // return to the list view that refered to this item
                        $this->referral = '&ref='.(string)$this->ref;
                }

		// Set the toolbar
		$this->addToolBar();

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}


	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId	= $user->id;
		$isNew = $this->item->id == 0;

		JToolbarHelper::title( JText::_($isNew ? 'COM_COSTBENEFITPROJECTION_INTERVENTION_NEW' : 'COM_COSTBENEFITPROJECTION_INTERVENTION_EDIT'), 'pencil-2 article-add');
		// [10269] Built the actions for new and existing records.
		if ($this->refid || $this->ref)
		{
			if ($this->canDo->get('intervention.create') && $isNew)
			{
				// [10281] We can create the record.
				JToolBarHelper::save('intervention.save', 'JTOOLBAR_SAVE');
			}
			elseif ($this->canDo->get('intervention.edit'))
			{
				// [10293] We can save the record.
				JToolBarHelper::save('intervention.save', 'JTOOLBAR_SAVE');
			}
			if ($isNew)
			{
				// [10298] Do not creat but cancel.
				JToolBarHelper::cancel('intervention.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				// [10303] We can close it.
				JToolBarHelper::cancel('intervention.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			if ($isNew)
			{
				// [10311] For new records, check the create permission.
				if ($this->canDo->get('intervention.create'))
				{
					JToolBarHelper::apply('intervention.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('intervention.save', 'JTOOLBAR_SAVE');
					JToolBarHelper::custom('intervention.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
				};
				JToolBarHelper::cancel('intervention.cancel', 'JTOOLBAR_CANCEL');
			}
			else
			{
				if ($this->canDo->get('intervention.edit'))
				{
					// [10338] We can save the new record
					JToolBarHelper::apply('intervention.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('intervention.save', 'JTOOLBAR_SAVE');
					// [10341] We can save this record, but check the create permission to see
					// [10342] if we can return to make a new one.
					if ($this->canDo->get('intervention.create'))
					{
						JToolBarHelper::custom('intervention.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
				$canVersion = ($this->canDo->get('core.version') && $this->canDo->get('intervention.version'));
				if ($this->state->params->get('save_history', 1) && $this->canDo->get('intervention.edit') && $canVersion)
				{
					JToolbarHelper::versions('com_costbenefitprojection.intervention', $this->item->id);
				}
				if ($this->canDo->get('intervention.create'))
				{
					JToolBarHelper::custom('intervention.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
				}
				JToolBarHelper::cancel('intervention.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		JToolbarHelper::divider();
		// [10378] set help url for this view if found
		$help_url = CostbenefitprojectionHelper::getHelpUrl('intervention');
		if (CostbenefitprojectionHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_COSTBENEFITPROJECTION_HELP_MANAGER', false, $help_url);
		}
	}

        /**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 30)
		{
    		// use the helper htmlEscape method instead and shorten the string
			return CostbenefitprojectionHelper::htmlEscape($var, $this->_charset, true, 30);
		}
                // use the helper htmlEscape method instead.
		return CostbenefitprojectionHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(JText::_($isNew ? 'COM_COSTBENEFITPROJECTION_INTERVENTION_NEW' : 'COM_COSTBENEFITPROJECTION_INTERVENTION_EDIT'));
		$document->addStyleSheet(JURI::root() . "administrator/components/com_costbenefitprojection/assets/css/intervention.css");
		// [8799] Add Ajax Token
		$document->addScriptDeclaration("var token = '".JSession::getFormToken()."';"); 
		$document->addScript(JURI::root() . $this->script);
		$document->addScript(JURI::root() . "administrator/components/com_costbenefitprojection/views/intervention/submitbutton.js");
		JText::script('view not acceptable. Error');
	}
}