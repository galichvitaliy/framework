<?php
/**
 * Created by PhpStorm.
 * User: galych
 * Date: 16.01.15
 * Time: 13:51
 */

namespace Mirage;

class Page extends Controller {

	public function show($id) {
		$page = \R::findOne( 'pages', ' _key = ? AND is_active=1 ', [ $id ]);
		$file = $page ? str_replace("/", '-', $page['_key']) : "";
		if($page && file_exists(App::get('root_dir')."/public/uploads/custom_page/".$file.'.tpl')) {

			$tpl = App::get('view');
			$tpl->addTemplateDir(App::get('root_dir')."/public/uploads/custom_page/", 'custom');

			$tpl->assign('title', htmlspecialchars_decode($page['title']));
			//$tpl->assign('text', htmlspecialchars_decode(file_get_contents(App::get('root_dir')."/public/uploads/custom_page/".$file.'.tpl')));
			$tpl->assign('text', $tpl->fetch("[custom]".$file.".tpl"));
			$tpl->assign('seo_title', !empty($page['seo_title']) ? htmlspecialchars_decode($page['seo_title']) : false);
			$tpl->assign('seo_keywords', !empty($page['seo_keywords']) ? htmlspecialchars_decode($page['seo_keywords']) : false);
			$tpl->assign('seo_description', !empty($page['seo_description']) ? htmlspecialchars_decode($page['seo_description']) : false);

			$tpl->display('custom_page.tpl');

		} else {
			$this->error();
		}

	}

	public function error() {

		$tpl = App::get('view');
		$tpl->display('404.tpl');
		exit();
	}

} 