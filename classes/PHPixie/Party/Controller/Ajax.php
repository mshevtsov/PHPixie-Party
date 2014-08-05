<?php

namespace PHPixie\Party\Controller;

class Ajax extends \App\Page {
	private $page = array();

	public function before() {
		if(!$this->logged_in('admin'))
			return $this->redirect("/register");
		$this->view = $this->pixie->view('admin2');
		$this->view->user = $this->pixie->auth->user();
	}

	public function after() {
		die(json_encode(array("status"=>"error")));
	}

	public function action_index() {
		throw new \PHPixie\Exception\PageNotFound;
	}

	// Переключаем режим льготной регистрации для пользователя по его номеру
	public function action_grace() {
		if($this->request->post('type')=='user') {
			if(is_numeric($this->request->post('id'))) {
				$user = $this->pixie->orm->get('user',$this->request->post('id'));
				if($user->loaded()) {
					if($user->grace) {
						$user->grace = 0;
						$user->save();
						die(json_encode(array("status"=>"success","result"=>"false")));
					}
					else {
						$user->grace = 1;
						$user->save();
						die(json_encode(array("status"=>"success","result"=>"true")));
					}
				}
			}
		}
		die(json_encode(array("status"=>"error")));
	}

	public function action_houses() {
		if($this->request->post('action')=='list') {
			$houses = $this->pixie->orm->get('house')->find_all()->as_array();
			$result = array();
			foreach($houses as $item) {
				if($item->coord)
					$result[] = array(
						'id' => (int)$item->id,
						'coord' => explode(",", $item->coord),
						'address' => trim(str_replace('"', "'", $item->address)),
						'places1' => (int)$item->places1,
						'places2' => (int)$item->places2,
						'places3' => (int)$item->places3,
						'places4' => (int)$item->places4,
						'meeting' => (int)$item->meeting,
						'name' => $item->name,
						);
			}
			die(json_encode($result));
		}
	}


	// Просто генерируем код активации пользователя
	// (использовал лишь раз, и то вручную, поэтому нужды пока нет)
	public function action_makemd5() {
		if($this->request->get('email'))
			die(sha1(mt_rand(10000,99999) . time() . $this->request->get('email')));
	}

	// Узнаём, сколько осталось заплатить участнику
	public function action_needtopay() {
		if($this->request->post('type')=='part') {
			if(is_numeric($this->request->post('id'))) {
				$sum = $this->pixie->party->getSumToPay($this->request->post('id'));
				if($sum!==false)
					die(json_encode(array("status"=>"success","result"=>$sum['needToFull'])));
			}
		}
	}

	// Повторное исполнение заказа
	public function action_approveorder() {
		if(is_numeric($this->request->post('id'))) {
			$order = $this->pixie->orm->get('order',$this->request->post('id'));
			if($order->loaded()) {
				$this->pixie->party->approveOrder($order, null, null, true);
				die(json_encode(array("status"=>"success")));
			}
		}
	}



	// Читаем сообщение для регистратора или от него
	public function action_message() {
		if($this->request->post('action')=='read') {
			if($this->request->post('type')=='user') {
				if(is_numeric($this->request->post('id'))) {
					$msg = $this->pixie->orm->get('usermsg',$this->request->post('id'));
					if($msg->loaded()) {
						$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
						$formatter->setPattern("d MMMM',' h:mm");
						die(json_encode(array("status"=>"success","result"=>$msg->content,"title"=>"Сообщение от {$msg->sender->name} за ". $formatter->format(new \DateTime($msg->date)))));
					}
				}
			}
		}
		else if($this->request->post('action')=='send') {
			if($this->request->post('type')=='user'
				&& is_numeric($this->request->post('id'))
				&& is_numeric($this->request->post('template'))
				) {
				$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
				$formatter->setPattern('d MMMM');
				$htmlText = "";

				$user = $this->pixie->orm->get('user',$this->request->post('id'));
				if($user->loaded()) {

					// Шаблон "Напомнить об активации"
					if($this->request->post('template')==1) {

						$htmlText .= "Мир вам, {$user->name}!

Вы указали этот email ({$user->email}) ". $formatter->format(new \DateTime($user->codesent)) ." при регистрации на конференцию Я МОЛОДОЙ! 2014.

Мы заметили, что на сегодняшний день вы по каким-то причинам не прошли регистрацию до конца. Напоминаем, что регистрация считается завершённой после добавления анкеты участника и внесения предоплаты. Можем ли у вас узнать, что вам помешало? Может быть, возникли какие-нибудь трудности с самой регистрацией, и вам нужна помощь?

Пожалуйста, смело обращайтесь: мы готовы ответить на ваши вопросы и постараемся помочь!
";
						if(strtotime($user->codesent) < strtotime('2014-07-01'))
							$htmlText .= "
Вы приступали к регистрации заблаговременно, и мы ценим вашу пунктуальность! К сожалению, на тот момент у нас действовала старая форма регистрации на сайте imolod.ru, которую мы заменили 1 июля на новый сайт со всеми действующими условиями. Просим у вас прощения за то, что вначале мы не предусмотрели предоплату 500 руб., а потом её пришлось ввести! И поэтому индивидуально для вас мы отменяем повышение общего регистрационного взноса после 5 августа до 2000 рублей: для вас он в любое время останется равным 1500 руб.
";
						if($user->pass && $user->active)
							$htmlText .= "
Для продолжения регистрации вам нужно войти на сайт с помощью формы для входа:
http://2014.imolod.ru/register#start
";
						else if($user->code)
							$htmlText .= "
Для продолжения регистрации вам нужно перейти по этой ссылке и придумать для себя пароль:
http://2014.imolod.ru/register/confirm?email={$user->email}&code={$user->code}
";

						$htmlText .= "
Затем вы откроете свой профиль для добавления анкет участников. Проверьте, пожалуйста, что все сведения в анкете указаны верно. Дальнейшие инструкции по оплате вы увидите, нажав кнопку оплаты.

Если у вас есть замечания или предложения по методу регистрации, сообщите их нам, пожалуйста!


Программа конференции:
https://vk.com/page-71444548_48399312

Подробные условия регистрации:
https://2014.imolod.ru/register

Инструкция по регистрации с картинками:
https://vk.com/page-71444548_48108588

Вопросы и ответы в группе ВКонтакте:
https://vk.com/board71444548

Благословений! Ждём вас!

--
Организаторы конференции Я МОЛОДОЙ! 2014 в Волгограде
info@imolod.ru
http://2014.imolod.ru
https://vk.com/imolod14
";

// echo $htmlText;


					} // template 1

					// Шаблон "Поторопить с оплатой"
					else if($this->request->post('template')==2) {
						$htmlText .= "Мир вам, {$user->name}!

Мы, организаторы \"Я молодой\", очень рады, что вы собираетесь на эту конференцию и ". $formatter->format(new \DateTime($user->codesent)) ." решили зарегистрироваться на нашем сайте! Сейчас мы вовсю готовимся, ведь до начала осталось всего ". $this->pixie->party->daysLeft() .".

Ваша регистрация очень важна для нас, чтобы спланировать все расходы и расселение. Ещё мы просим всех участников пожертвовать на нужды конференции по ". $this->pixie->config->get('party.periods.1.prices.full') ." рублей, из которых ". $this->pixie->config->get('party.periods.1.prices.prepay') ." нужно отправить заранее (можно и позже, но тогда вся сумма выйдет больше). Наше общее желание - чтобы во время всей конференции молодёжь могла вместе поклоняться Богу спокойно от бытовых проблем и суеты!

";
$partsCount = $user->participants->where('cancelled',0)->count_all();
if($partsCount > 1) {
	$partsUnpayed = $user->participants->where('cancelled',0)->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'))->find_all()->as_array();
	$names = array();
	foreach($partsUnpayed as $part)
		$names[] = $part->firstname .' '. $part->lastname;
	$htmlText .= "Вы уже добавили анкеты {$partsCount} ". $this->pixie->party->declension($partsCount, array('участника','участников','участников')) .", и для успешного завершения регистрации осталось внести предоплату за следующих из них: ". implode(", ", $names) .".";
}
else if($partsCount)
	$htmlText .= "Вы уже добавили свою анкету участника, и для успешного завершения регистрации осталось только внести предоплату.";
else
	$htmlText .= "Для успешного завершения регистрации вам нужно заполнить свою анкету участника и внести предоплату.";

$htmlText .= "

А сделать нам денежный перевод совсем не сложно, так как доступны разные способы оплаты, и многие участники уже с этим справились. По этой ссылке вы перейдёте к подробным условиям и ко входу в свой профиль на сайте:
http://2014.imolod.ru/register

Наша группа Вконтакте, в которой есть программа конференции и ответы на вопросы:
http://vk.com/imolod14

";

if(!$user->grace) {
	if(strtotime('today')==strtotime($this->pixie->config->get('party.periods.1.lastday')))
		$htmlText .= "Сегодня, ". $formatter->format(new \DateTime('today')) .", последний день для внесения предоплаты, чтобы для вас общий размер взноса за одного участника сохранился равным ". $this->pixie->config->get('party.periods.1.prices.full') ." руб. ";
	else if(strtotime('tomorrow')==strtotime($this->pixie->config->get('party.periods.1.lastday')))
		$htmlText .= "Завтра, ". $formatter->format(new \DateTime('tomorrow')) .", последний день для внесения предоплаты, чтобы для вас общий размер взноса за одного участника сохранился равным ". $this->pixie->config->get('party.periods.1.prices.full') ." руб. ";
}
$htmlText .= "Пожалуйста, смело обращайтесь к нам за помощью или с вопросами - мы готовы ответить и пойти вам на встречу!

--
Команда организаторов конференции \"Я МОЛОДОЙ\" в Волгограде
info@imolod.ru
http://2014.imolod.ru
http://vk.com/imolod14
";




					} // template 2

					// Шаблон "Поторопить с оплатой"
					else if($this->request->post('template')==3) {
						$htmlText .= "Мир вам, {$user->name}!

Система регистрации нам показывает, что вы заполнили анкету участника и пробовали отправить регистрационный взнос, но отправка не получилась. Вы можете проверить нынешнее состояние оплаты в своём профиле, в таблице с участниками:
http://". $this->pixie->config->get('party.event.domain') ."/register/profile

Если вы совершали оплату через банковский перевод или Сбербанк Онлайн, то нормальный срок обработки составляет 2 рабочих дня. Остальные виды платежей исполняются всего за несколько минут. Если все сроки вышли, а в профиле сумма оплаты не появилась, значит платёж неудачный.

Можем ли мы вам помочь? Пожалуйста, обращайтесь к нам со всеми вопросами и замечаниями! Осталось всего ". $this->pixie->party->daysLeft() ." до конференции, но ещё можно успеть. Мы учитываем, что вы пытались всё сделать ещё до конца льготного периода регистрации, поэтому, если вы сообщите нам свою проблему, размер взноса для вас не увеличится.

--
Команда организаторов конференции \"Я МОЛОДОЙ\" в Волгограде
info@imolod.ru
http://2014.imolod.ru
http://vk.com/imolod14
";
// die($htmlText);
					} // template 3

					// Осталось отправить, если есть что:
					if($htmlText) {
						$result = $this->pixie->email->send(
							array($user->email => "=?utf-8?B?". base64_encode($user->name) ."?="),
							array($this->pixie->config->get('party.contacts.info') => "=?utf-8?B?". base64_encode('Организаторы '. $this->pixie->config->get('party.event.short_title')) ."?="),
							"Ваша регистрация на ". $this->pixie->config->get('party.event.short_title'),
							$htmlText,
							false
							);
						if($result) {
							$newMsg = $this->pixie->orm->get('usermsg');
							$newMsg->content = $htmlText;
							$newMsg->type = 'email';
							$newMsg->date = date('Y-m-d H:i:s',time());
							$newMsg->save();
							$newMsg->add('sender', $this->pixie->auth->user());
							$newMsg->add('recipient', $user);
							$newMsg->save();
							die(json_encode(array("status"=>"success")));
						}
					} // Отправка

				}
			}
		}
	}


}
