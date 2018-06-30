<?php
$base = $_SERVER['HOME'] . "/apps/modanellsons";
include($base . '/public/wp-blog-header.php');
require_once('decode.php');

define("RATE", 1);
$collections = $base . "/public/import";
$categories = json_decode(file_get_contents($base."/import/run/categories.json"), true);
//nellcorp_import_categories();die();

if(empty($imported = json_decode(file_get_contents($base."/import/run/temp/imported.json"), true))){
	$imported = array();	
}

//print_r($imported);

echo "Import Started: ". date('l jS \of F Y h:i:s A') . "\n\n";


$site_url = str_replace("http", "https", get_site_url());
//Read product images from temporary directory
$images = get_images();
//print_r($images);echo("\n");die();
$products = array();

//Decode Images into products and insert into product array to be imported
foreach($images as $image){
	$product = decode($image, $categories);
	//print_r($product);
	if(empty($product)){continue;}

	if($product['original'] != ''){
		if(array_key_exists($product['code'], $products)){
			$products[$product['code']]['dupes'][] = $product['url'];
		}else{
			$products[$product['code']] = $product;
			$products[$product['code']]['original'] = '';
			$products[$product['code']]['dupes'] = array($product['url']);
		}
		
	}else{
		if(array_key_exists($product['code'], $products)){
			$product['dupes'] = array_merge($products[$product['code']]['dupes'],$product['dupes']);
		}
		$products[$product['code']] = $product;	
		
	}
}

//foreach ($products as $key => $prod) { echo 'Code: ' . $prod['code'] . ' Dupes: ' . implode(' | ', $prod['dupes']) . "\n";}die();

//Import categogories
//nellcorp_import_categories();

//Build CSV
//$fp = fopen($base.'/public/import/products.csv', 'w');
$fp = fopen($base.'/public/wp-content/uploads/wpallimport/files/products.csv', 'w');
$fields = array("sku","product_type","parent_sku","name","description","category","cat_desc","catparent_desc","price","size","color","brand","images");
fputcsv($fp, $fields);

$sizes = array('xs','s','l','xl');
foreach ($products as $product) {

	if(array_key_exists($product['code'], $imported) and (!isset($argv[1]) or $argv[1] != 'update')){ continue; }

	$gallery = (empty($product['dupes']))?$product['url']:$product['url'] .'|'.implode('|', $product['dupes']);

$fields = array($product['code'],$product['type'],$product['master'],$product['name'],$product['name'],$product['parent'] .">".$product['category'],
	$product['category_desc'],$product['catparent_desc'],ceil($product['price']*RATE),'m',$product['color'],$product['brand'],$gallery);

fputcsv($fp, $fields);
$imported[$product['code']] = array('url' => $product['url'],'type'=>$product['type'],'master'=>$product['master'],'color'=>$product['color'],'brand'=>$product['brand']);
//echo "New Product: "; print_r($imported[$product['code']]); echo "\n";

foreach ($sizes as $size) {
	$master = ($product['type'] == 'variation')?$product['master']:$product['code'];
	$fields = array($product['code'].$size,'variation',$master,$product['name'],$product['name'],$product['parent'] .">".$product['category'],
	$product['category_desc'],$product['catparent_desc'],ceil($product['price']*RATE),$size,$product['color'],$product['brand'],$gallery);

	fputcsv($fp, $fields);
	$imported[$product['code']] = array('url' => $product['url'],'type'=>'variation','master'=>$master,'size'=>$size,'color'=>$product['color'],'brand'=>$product['brand']);
}

}
fclose($fp);
file_put_contents($base."/import/run/temp/imported.json", json_encode($imported, JSON_PRETTY_PRINT));
//Import Categories
//nellcorp_import_categories();

//if(!empty($imported)){clear_imported($dropbox, $imported, $local, $remote);}


?> 