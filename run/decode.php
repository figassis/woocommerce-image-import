<?php 

function listdir($dir){
    // array to hold return value
    $retval = array();

    // add trailing slash if missing
    if(substr($dir, -1) != "/") $dir .= "/";

    // open pointer to directory and read list of files
    $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
    while(false !== ($entry = $d->read())) {
      // skip hidden files
      if($entry[0] == ".") continue;
      if(is_dir("$dir$entry")) {
        $retval[] = array(
          "name" => "$dir$entry/",
          "type" => filetype("$dir$entry"),
          "size" => 0,
          "lastmod" => filemtime("$dir$entry")
        );
      } elseif(is_readable("$dir$entry")) {
        $retval[] = array(
          "name" => "$dir$entry",
          "type" => mime_content_type("$dir$entry"),
          "size" => filesize("$dir$entry"),
          "lastmod" => filemtime("$dir$entry")
        );
      }
    }
    $d->close();

    return $retval;
  }

function get_images(){
  global $base;
  $ready = $base .'/import/run/temp/ready.txt';
  $collections = file($ready);
  $images = array();
  
  foreach ($collections as $collection) {
    $dir = substr($collection, 0,-2);
    $temp = listdir($dir);
    
    foreach ($temp as $file) {
      if($file['type'] == "image/jpeg"){
        $images[] = $file['name'];
      }
    }
  }
  return $images;
}

//find item category, subcategory, name and number from full code
function decode($filename){
global $categories;
global $site_url;
global $base;
$code = explode('.',basename($filename));
$code = $code[0]; //NR-VT-001_brand-color_950a.jpg
//var_dump($code);die();
//NR = Roupa de noiva
//VTN = vestido
//107 = vestido de noiva #
//132 = supplier price
//209 = supplier price + shipping + import taxes
//985 = sale price
//a = gallery image

$parts = explode('_',$code);
$product = explode('-',$parts[0]);
$cat = strtoupper($product[0] . '-' . $product[1]);
$price = $parts[2];
$attr = explode('-',$parts[1]);

if(!array_key_exists(strtoupper($cat), $categories)){return false;}

$item = $product[0] . '-' . $product[1] . '-' . $product[2];

$pname = str_replace($base . "/public/import/", '', $filename);
$info['code'] = $item;
$info['full'] = $code;
$info['url'] = rtrim($site_url,'/').'/import/'.$pname;
$info['name'] = $categories[$cat]['product_desc'];
$info['category'] = $cat;
$info['parent'] = $product[0];
$info['category_desc'] = $categories[$cat]['cat_desc'];
$info['catparent_desc'] = $categories[$product[0]]['cat_desc'];
$info['original'] = '';
$info['master'] = ''; 
$info['type'] = 'master'; 
$info['dupes'] = array(); 
$info['brand'] = $attr[0];
$info['color'] = $attr[1];
$info['price'] = $price;

//Check if this is just a gallery image
if(!is_numeric($price)){
	$info['price'] = substr($price, 0,strlen($price) -1);
	$info['original'] = substr($code, 0,strlen($code) -1);
}

//Check if this a product variation
if(!is_numeric($product[2])){
  $info['master'] = substr($item, 0,strlen($item) -1);
  $info['type'] = 'variation'; 
}
//if($item == "NR-VT-548"){var_dump($info);die();}
return $info;
}

function nellcorp_import_categories(){
    global $categories;
        
    if(!empty($categories)){
    //print_r($categories);die();
      foreach($categories as $category){
        if(count(explode('-', $category['code']))==1){
          //echo "Parent: ".$category['code']."\n";

          $term = term_exists(strtolower($category['code']), 'product_cat', 0); // array is returned if taxonomy is given
          //echo "WP Term: ".print_r($term) ."\n";
          
          if ((int)$term['term_id'] == 0) {
            //echo "Inserting\n\n";
            $term = wp_insert_term(
              $category['cat_desc'],
              'product_cat',
              array(
                'parent'=> 0,
                'slug'=> strtolower($category['code'])
              ));
            delete_option('product_cat_children'); // clear the cache
            
          }

          foreach($categories as $sub){
            $temp = explode('-', $sub['code']);
            
            if (count($temp) == 2 and $temp[0] == $category['code']) {
            //  echo "Child: ".$sub['code']."\n";           

              $parent = term_exists(strtolower($temp[0]), 'product_cat', 0);
              $child = term_exists(strtolower($sub['code']), 'product_cat', 0);
              $parentid = $parent['term_id'];
              
              if($category['code'] == 'MC' and strlen($temp[1])== 3 and substr($temp[1], 2,1) == "N"){
                $parentid = 0;
              }

              if ((int)$child['term_id'] == 0 and (int)$parent['term_id'] != 0) {
            //    echo "Not in WP. Inserting: ".$sub['code']."\n";            
                $term = wp_insert_term(
                  $sub['cat_desc'],
                  'product_cat',
                  array(
                    'parent'=> $parentid,
                    'slug'=> strtolower($sub['code'])
                  ));
                delete_option('product_cat_children'); // clear the cache
              }
            }
          }
        }
      }
    }
  }

function clear_imported($images){
  foreach($images as $image){
    if(file_exists($image)){
      unlink($image);
    }
  }
}

?>
