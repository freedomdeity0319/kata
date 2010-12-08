<?php
/**
 * @package kata_scaffold
 */







/**
 * default scaffolding-controller. extend it and use the hooks to costumize functionality
 * @package kata_scaffold
 * @author mnt@codeninja.de
 */
class ScaffoldController extends AppController {

	// hooks, overwrite them if you want

	function beforeDelete($id = null) {
		return true;
	}

	function beforeUpdate($id = null) {
		return true;
	}

	function beforeInsert($data = null) {
		return true;
	}

	// the scaffolder

	function beforeAction() {
		parent::beforeAction();
	}
	function beforeFilter() {
		parent::beforeFilter();
	}
	function afterFilter() {
		parent::afterFilter();
	}

	private $MyModel = null;
	private function findModelToScaffold() {
		if (!is_array($this->uses) || (count($this->uses) == 0)) {
			throw new Exception('No Model to scaffold found');
		}

		$name = $this->uses[count($this->uses) - 1];
		$this->MyModel = getModel($name);
	}

	final function index($page = 0) {
		$this->layout = '../../lib/layouts/scaffold';
		$this->findModelToScaffold();
		$this->set('schema', $this->MyModel->describe());
		$this->set('pages', $this->MyModel->readPageCount());
		$this->set('page', $page);
		$this->set('data', $this->MyModel->readPage($page));
		$this->render('../../lib/scaffold/index');
	}

	final function delete($id = null, $page = 0) {
		if (!$this->beforeDelete($id)) {
			return;
		}

		if (isset ($id) && is_numeric($id)) {
			$this->findModelToScaffold();
			$this->MyModel->delete($id);
		}
		$this->redirect($this->params['controller'] . '/index/' . (int) $page);
	}

	final function update($id = null) {
		$this->layout = 'scaffold';

		if (isset ($this->params['form']['id']) && is_numeric($this->params['form']['id'])) {
			if ($this->beforeUpdate($id)) {
				$this->findModelToScaffold();
				$schema = $this->MyModel->describe();
				$data = $this->params['form']['data'];
				$id = $data[$schema['primary']];
				unset ($data[$schema['primary']]);
				if ($this->MyModel->update($id, $data)) {
					$this->redirect('/' . $this->params['controller'] . '/index');
					return;
				}
			}
		}

		if (isset ($id) && is_numeric($id)) {
			$this->findModelToScaffold();
			$this->set('update', $id);
			$this->set('schema', $this->MyModel->describe());
			$data = $this->MyModel->read($id);
			$this->set('data', array_shift($data));
			$this->set('formData', isset ($this->params['form']['data']) ? $this->params['form']['data'] : array ());
			$this->render('../../lib/scaffold/record');
			return;
		}

		$this->redirect($this->params['controller'] . '/index/');
	}

	final function insert() {
		$this->layout = 'scaffold';
		$this->set('update', 0);

		if (isset ($this->params['form']['data']) && is_array($this->params['form']['data'])) {
			if ($this->beforeInsert($this->params['form']['data'])) {
				$this->findModelToScaffold();
				$schema = $this->MyModel->describe();
				$data = $this->params['form']['data'];
				$id = $data[$schema['primary']];
				unset ($data[$schema['primary']]);
				if ($this->MyModel->create($data)) {
					$this->redirect('/' . $this->params['controller'] . '/index');
					return;
				}
			}
		}

		$this->findModelToScaffold();
		$this->set('schema', $this->MyModel->describe());
		$this->set('data', array ());
		$this->set('formData', isset ($this->params['form']['data']) ? $this->params['form']['data'] : array ());
		$this->render('../../lib/scaffold/record');
	}

}
