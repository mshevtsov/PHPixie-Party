<?php

namespace PHPixie\Party\Controller;

class Admin extends \App\Page {


	public function before() {
		if(!$this->logged_in('admin'))
			return $this->redirect("/register");
		$this->view = $this->pixie->view('admin');
		$this->view->user = $this->pixie->auth->user();
	}








	public function action_index() {
		$this->view->page['content'] = 'Wazzup!';
	}




















	public function action_logout() {
		$this->pixie->auth->logout();
		$this->redirect('/register');
	}

















	public function action_list() {

		if(!$model = $this->request->param('model'))
			return $this->redirect("/admin/");

		if($this->request->method == 'POST') {
			if($order = $this->request->post('order'))
				$this->orderBranch(json_decode($order), 0, $model);
			return $this->redirect("/admin/list/". $model);
		}

		$this->view->model = $model;
		if(!$this->pixie->orm->get($model)) {
			die("bad model");
		}
		$this->view->columns = $this->pixie->orm->get($model)->columns();
		// $this->view->columnsText = $this->pixie->config->get('admin.columns');


		if(in_array('date', $this->view->columns)) {
			$this->view->items = $this->pixie->orm->get($model)
				->order_by('date','desc')
				->find_all()->as_array(true);
			$this->view->subview = 'admin-list';
		}
		else {
			$this->view->items = $this->pixie->orm->get($model)
				->find_all()->as_array(true);
			$this->view->page['subview'] = 'admin-list';
		}

	}
















	public function action_add() {
		if(!$this->logged_in('admin'))
			return;
		$this->view->user = $this->pixie->auth->user();

		if($this->request->param('model'))
			$model = $this->request->param('model');
		else
			return $this->redirect("/admin/");

		$this->view->subview = 'admin-add';
		$this->view->model = $model;
		$this->view->columns = $this->pixie->orm->get($model)->columns();
		$columnsText = $this->pixie->config->get('admin.columns');
		$this->view->columnsText = $columnsText;

		if($this->request->method == 'POST') {
			$editItem = $this->pixie->orm->get($model);
			$this->saveItem($editItem, $model);
			return $this->redirect("/admin/add/". $model);
		}

		$this->view->select = $this->selectOptions($model);
	}



















	public function action_edit() {
		if(!$this->logged_in('admin'))
			return;
		$this->view->user = $this->pixie->auth->user();

		if(!($model = $this->request->param('model'))
			|| !($id = $this->request->param('id')))
			return $this->redirect("/admin/");

		$columnsText = $this->pixie->config->get('admin.columns');

		// Если мы сохраняем изменения, сделанные пользователем
		if($this->request->method == 'POST') {
			$editItem = $this->pixie->orm->get($model, $id);
			$this->saveItem($editItem, $model);
			return $this->redirect("/admin/list/". $model);
		}

		// Если мы только формируем страницу с формой редактирования
		else {

			$this->view->subview = 'admin-add';

			$this->view->model = $model;
			$this->view->id = $id;
			$this->view->item = $this->pixie->orm->get($model, $id);
			if(!$this->view->item->loaded())
				return $this->redirect("/admin/list/". $model);

			// Узнаём, какие у модели есть поля, их названия и типы
			$this->view->columns = $this->pixie->orm->get($model)->columns();
			$this->view->columnsText = $columnsText;

			// Готовим содержимое комбобоксов из связей "один-к-одному"
			$this->view->select = $this->selectOptions($model, $id);

			// Выводим сортируемый список связей для использования
			if(isset($this->pixie->orm->get($model)->has_many)) {
				$has_many = $this->pixie->orm->get($model)->has_many;

				// Чтобы предусмотреть несколько видов связей
				$excluded = $included = array();
				foreach($has_many as $key => $value) {
					$included[$key] = $this->view->item->$key->find_all()->as_array(true);

					// Собираем номера уже добавленных
					$ids = array();
					foreach($included[$key] as $item)
						$ids[] = $item->id;

					// И собираем оставшиеся связи
					if(count($ids))
						$excluded[$key] = $this->pixie->orm->get($value['model'])
							// ->where("parent",0) // придётся без иерархии
							->where('id', 'NOT IN', $this->pixie->db->expr('('.implode(',', $ids).')'))
							// ->order_by('weight','asc')
							->order_by('title','asc')
							->find_all();
					else
						$excluded[$key] = $this->pixie->orm->get($value['model'])
							// ->where("parent",0)
							// ->order_by('weight','asc')
							->order_by('title','asc')
							->find_all();
				}
				$this->view->excluded = $excluded;
				$this->view->included = $included;
			}

		}
	}



















	public function action_clearfield() {
		if(!$this->logged_in('admin'))
			return;
		$this->view->user = $this->pixie->auth->user();

		$columnsText = $this->pixie->config->get('admin.columns');

		if(($field = $this->request->param('field'))
			&& ($model = $this->request->param('model'))
			&& ($id = $this->request->param('id'))) {

			$item = $this->pixie->orm->get($model, $id);
			if(isset($item->$field)) {
				if($columnsText[$field]['type']=='image')
					$this->pixie->filesafe->deleteImage($item->$field, $model);
				else if($columnsText[$field]['type']=='file')
					$this->pixie->filesafe->deleteDocument($item->$field, $model);
				$item->$field = null;
				$item->save();
			}

		}
		return $this->redirect("/admin/edit/{$model}/{$id}");
	}

















	public function action_delete() {
		if(!$this->logged_in('admin'))
			return;
		$this->view->user = $this->pixie->auth->user();

		if($this->request->param('model') && $this->request->param('id')) {
			$model = $this->request->param('model');
			$id = $this->request->param('id');
		}
		else
			return $this->redirect("/admin/");
		$this->pixie->orm->get($model, $id)->delete();
		return $this->redirect("/admin/list/". $model);
	}












	// Поле выбора ссылки на объект из другой таблицы превращаем в комбо
	private function selectOptions($model, $id=false) {
		if(!isset($this->pixie->orm->get($model)->belongs_to))
			return false;
		$belongs = $this->pixie->orm->get($model)->belongs_to;
		$select = array();
		foreach($belongs as $blng) {
			$select[$blng['key']] = array(
				'elements' => $this->pixie->orm->get($blng['model'])->order_by('title','asc')->find_all()->as_array(true),
				'selected' => ($id ? $this->pixie->orm->get($model,$id)->$blng['name']->id : 0),
			);
		}
		return $select;
	}



	private function orderBranch($items, $parent, $model) {
		$weight = 0;
		foreach($items as $item) {
			$edit = $this->pixie->orm->get($model, $item->id);
			if(isset($edit->parent))
				$edit->parent = $parent;
			if(isset($edit->weight))
				$edit->weight = $weight;
			$edit->save();
			$weight++;
			if(isset($item->children))
				$this->orderBranch($item->children, $item->id, $model);
		}
	}

	private function flatNestable($list, &$newlist) {
		foreach($list as $item) {
			array_push($newlist, $item->id);
			if(isset($item->children))
				$this->flatNestable($item->children, $newlist);
		}
	}




	// Универсальный метод для сохранения только что созданного
	// или изменённого материала
	private function saveItem($editItem, $model) {
		$postFields = $this->request->post();
		$columns = $this->pixie->orm->get($model)->columns();
		$columnsText = $this->pixie->config->get('admin.columns');

		foreach($postFields as $field=>$value) {
			if($field=='id' || !isset($columnsText[$field]))
				continue;
			// Обрабатываем поле - список связей типа "один-ко-многим"
			else if($columnsText[$field]['type']=='list') {

				// Смотрим параметры данной связи
				if(!isset($this->pixie->orm->get($model)->has_many[$field]))
					continue;
				$has_many = $this->pixie->orm->get($model)->has_many[$field];

				// Получаем новый список связей и делаем его одноуровневым
				$list = array();
				$this->flatNestable(json_decode($value), $list);

				// Удаляем все имеющиеся связи, которых нет в новом списке
				$children = $editItem->$field->find_all();
				foreach($children as $child)
					if(!in_array($child->id, $list))
						$editItem->remove($field, $child);

				// Добавляем новые недостающие связи
				foreach($list as $item) {
					$rel = $this->pixie->orm->get($has_many['model'], $item);
					$editItem->add($field, $rel);
				}

			}
			else
				$editItem->$field = $value;
		}

		// Повторная проверка всех полей на снятые галочки чекбоксов
		foreach($columns as $id=>$field) {
			if($columnsText[$field]['type']=='boolean') {
				if(isset($postFields[$field]) && !empty($postFields[$field]))
					$editItem->$field = 1;
				else
					$editItem->$field = 0;
			}
		}

		// Приём картинок и файлов
		foreach($_FILES as $field=>$file) {
			if($file['name']) {
				if($columnsText[$field]['type']=='image') {
					if($filename = $this->pixie->filesafe->getFileImage($field, $model))
						$editItem->$field = $filename;
				}
				else if($columnsText[$field]['type']=='file') {
					if($filename = $this->pixie->filesafe->getFileGeneral($field, $model))
						$editItem->$field = $filename;
				}
			}
		}
		$editItem->save();
		return true;
	}

}
