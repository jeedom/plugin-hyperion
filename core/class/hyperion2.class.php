<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class hyperion2 extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Méthodes d'instance************************* */

	public function preSave() {
		if ($this->getConfiguration('port') == '') {
			$this->setConfiguration('port', 19444);
		}
	}

	public function preUpdate() {
		if ($this->getConfiguration('ip') == '') {
			throw new Exception(__('L\'adresse IP ne peut être vide', __FILE__));
		}
	}

	public function postSave() {
		$color = $this->getCmd(null, 'color');
		if (!is_object($color)) {
			$color = new hyperion2Cmd();
			$color->setLogicalId('color');
			$color->setIsVisible(1);
			$color->setName(__('Couleur', __FILE__));
			$color->setOrder(0);
		}
		$color->setType('action');
		$color->setSubType('color');
		$color->setEqLogic_id($this->getId());
		$color->save();

		$clear = $this->getCmd(null, 'clear');
		if (!is_object($clear)) {
			$clear = new hyperion2Cmd();
			$clear->setLogicalId('clear');
			$clear->setIsVisible(1);
			$clear->setName(__('Remise à zéro', __FILE__));
			$clear->setOrder(1);
		}
		$clear->setType('action');
		$clear->setSubType('other');
		$clear->setEqLogic_id($this->getId());
		$clear->save();

		$effects = array('Knight rider', 'Blue mood blobs', 'Cold mood blobs', 'Full color mood blobs', 'Green mood blobs', 'Red mood blobs', 'Warm mood blobs', 'Rainbow mood', 'Rainbow swirl fast', 'Rainbow swirl', 'Snake', 'Strobe blue', 'Strobe Raspbmc', 'Strobe white');
		foreach ($effects as $effect) {
			$cmd = $this->getCmd(null, $effect);
			if (!is_object($cmd)) {
				$cmd = new hyperion2Cmd();
				$cmd->setLogicalId($effect);
				$cmd->setIsVisible(1);
				$cmd->setName($effect);
				$cmd->setOrder(1);
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}
	}

	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}
		$_version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $_version) == 1) {
			return '';
		}
		$vcolor = 'cmdColor';
		if ($version == 'mobile') {
			$vcolor = 'mcmdColor';
		}
		$parameters = $this->getDisplay('parameters');
		$cmdColor = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		if (is_array($parameters) && isset($parameters['background_cmd_color'])) {
			$cmdColor = $parameters['background_cmd_color'];
		}
		$replace = array(
			'#name#' => $this->getName(),
			'#id#' => $this->getId(),
			'#background_color#' => $this->getBackgroundColor($_version),
			'#eqLink#' => $this->getLinkToConfiguration(),
			'#cmdColor#' => $cmdColor,
			'#color#' => '',
			'#clear#' => '',
			'#select_effect#' => '<option disabled selected>' . __('Effet...', __FILE__) . '</option>',
			'#uid#' => 'sonos' . $this->getId() . self::UIDDELIMITER . mt_rand() . self::UIDDELIMITER,
		);
		$color = $this->getCmd(null, 'color');
		if (is_object($color)) {
			$replace['#color#'] = $color->toHtml($_version, '', $cmdColor);
		}

		$clear = $this->getCmd(null, 'clear');
		if (is_object($clear)) {
			$replace['#clear#'] = $clear->toHtml($_version, '', $cmdColor);
		}

		foreach ($this->getCmd('action') as $cmd) {
			if ($cmd->getIsVisible() == 1 && $cmd->getDisplay('hideOn' . $_version) != 1 && $cmd->getLogicalId() != 'color' && $cmd->getLogicalId() != 'clear') {
				$replace['#select_effect#'] .= '<option value="' . $cmd->getId() . '">' . $cmd->getName() . '</option>';
			}
		}
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$replace['#' . $key . '#'] = $value;
			}
		}

		$html = template_replace($replace, getTemplate('core', $_version, 'hyperion', 'hyperion2'));
		return $html;
	}
	/*     * **********************Getteur Setteur*************************** */
}

class hyperion2Cmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {
		$data = array();
		$eqLogic = $this->getEqLogic();
		if ($this->getLogicalId() == 'clear') {
			$data['command'] = 'clearall';
		} else if ($this->getLogicalId() == 'color') {
			$hex = str_replace("#", "", $_options['color']);
			if (strlen($hex) == 3) {
				$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
				$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
				$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
			} else {
				$r = hexdec(substr($hex, 0, 2));
				$g = hexdec(substr($hex, 2, 2));
				$b = hexdec(substr($hex, 4, 2));
			}
			$data['command'] = 'color';
			$data['priority'] = 100;
			$data['color'] = array($r, $g, $b);
		} else {
			$data['command'] = 'effect';
			$data['priority'] = 100;
			$data['effect'] = array('name' => $this->getLogicalId());
		}

		if (count($data) > 0) {
			$value = json_encode($data) . "\n";
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, $eqLogic->getConfiguration('ip'), $eqLogic->getConfiguration('port', 19444));
			$result = socket_write($socket, $value, strlen($value));
			socket_close($socket);
			if ($result === false) {
				throw new Exception(socket_strerror(socket_last_error()));
			}
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
