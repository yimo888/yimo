<?php
header("Content-Type:text/html;charset=utf-8");

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');

$image = new cls_image($_CFG['bgcolor']);

$basepath = ROOT_PATH;

function randomkeys($length)
{
 $key = "";
 $pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
 for($i=0;$i<$length;$i++)
 {
   $key.= $pattern{mt_rand(0,35)};    //生成php随机数
 }
 return $key;
}

function compressImg($Image,$Dw,$Dh,$Type,$newimage){  
	$newin = iconv('UTF-8','GB2312',$Image);
    IF(!file_exists($newin)){  
        echo "不存在图片";  
        return false;  
    }  
    // 如果需要生成缩略图,则将原图拷贝一下重新给$Image赋值(生成缩略图操作)  
    // 当Type==1的时候，将不拷贝原图像文件，而是在原来的图像文件上重新生成缩小后的图像(调整尺寸操作)  
    //IF($Type!=1){  
        copy($newin,$newimage);  
        $Image=$newimage;  
    //}  
    // 取得文件的类型,根据不同的类型建立不同的对象  
    $ImgInfo=getimagesize($Image);  
    Switch($ImgInfo[2]){  
        case 1:  
            $Img =@imagecreatefromgif($Image);  
            break;  
        case 2:  
            $Img =@imagecreatefromjpeg($Image);  
            Break;  
        case 3:  
            $Img =@imagecreatefrompng($Image);  
            break;  
    }  
    // 如果对象没有创建成功,则说明非图片文件  
    IF(Empty($Img)){  
        // 如果是生成缩略图的时候出错,则需要删掉已经复制的文件  
        IF($Type!=1){  
            unlink($Image);  
        }  
        return false;  
    }  
    // 如果是执行调整尺寸操作则  
    IF($Type==1){  
        $w=ImagesX($Img);  
        $h=ImagesY($Img);  
        $width = $w;  
        $height = $h;  
        IF($width>$Dw){  
            $Par=$Dw/$width;  
            $width=$Dw;  
            $height=$height*$Par;  
            IF($height>$Dh){  
                $Par=$Dh/$height;  
                $height=$Dh;  
                $width=$width*$Par;  
            }  
        } ElseIF($height>$Dh) {  
            $Par=$Dh/$height;  
            $height=$Dh;  
            $width=$width*$Par;  
            IF($width>$Dw){  
                $Par=$Dw/$width;  
                $width=$Dw;  
                $height=$height*$Par;  
            }  
        } Else {  
            $width=$width;  
            $height=$height;  
        }  
        $nImg =ImageCreateTrueColor($width,$height);// 新建一个真彩色画布  
        ImageCopyReSampled($nImg,$Img,0,0,0,0,$width,$height,$w,$h);// 重采样拷贝部分图像并调整大小  
        ImageJpeg($nImg,$Image,100);// 以JPEG格式将图像输出到浏览器或文件  
        return true;  
    } Else {// 如果是执行生成缩略图操作则  
        $w=ImagesX($Img);  
        $h=ImagesY($Img);  
        $width = $w;  
        $height = $h;  
        $nImg =ImageCreateTrueColor($Dw,$Dh);  
        IF($h/$w>$Dh/$Dw){// 高比较大  
            $width=$Dw;  
            $height=$h*$Dw/$w;  
            $IntNH=$height-$Dh;  
            ImageCopyReSampled($nImg, $Img, 0, -$IntNH/1.8, 0, 0, $Dw, $height, $w, $h);  
        } Else {// 宽比较大  
            $height=$Dh;  
            $width=$w*$Dh/$h;  
            $IntNW=$width-$Dw;  
            ImageCopyReSampled($nImg, $Img,-$IntNW/1.8,0,0,0, $width, $Dh, $w, $h);  
        }  
        ImageJpeg($nImg,$Image,100);  
        return true;  
    }  
}

function GrabImage($url,$filename="") {  
    if($url=="") return false;  
    if($filename=="") {  
        $ext=strrchr($url,".");  
        if(strtolower($ext)!=".gif" && strtolower($ext)!=".jpg" && strtolower($ext)!=".png")  
            return false;  
        $filename=date("YmdHis").$ext;  
    }  
    ob_start();   
    readfile($url);   
    $img = ob_get_contents();   
    ob_end_clean();  
    $size = strlen($img);   
  
    $fp2=@fopen($filename, "a");  
    fwrite($fp2,$img);  
    fclose($fp2);  
    return $filename;  
}

function addimage($gid,$imgurl,$isdefault = false,$isremote = false){
	global $db;
	global $ecs;
	global $basepath;
	//获取系统配置的高宽
	$tw = $db->getOne("SELECT `value` FROM ".$ecs->table('shop_config')." where code='thumb_width'");
	$th = $db->getOne("SELECT `value` FROM ".$ecs->table('shop_config')." where code='thumb_height'");
	$iw = $db->getOne("SELECT `value` FROM ".$ecs->table('shop_config')." where code='image_width'");
	$ih = $db->getOne("SELECT `value` FROM ".$ecs->table('shop_config')." where code='image_height'");
	
	$urlargs = explode('/',$imgurl);
	$filename = end($urlargs);
	$ext=strrchr($imgurl,".");
	$filename = str_replace($ext,"",$filename);
	echo($filename."<br>"."\n");
	if(strtolower($ext)!=".gif" && strtolower($ext)!=".jpg" && strtolower($ext)!=".png")  
		return false; 
	$orgpath = date("Ymd");
	$orgimg = $orgpath."/".$filename.$ext;
	
	$imgpath = "images/".date("Ym");
	$imgdir = $basepath."/".$imgpath;
	make_dir($basepath."/".$imgpath);
	make_dir($basepath."/".$imgpath."/goods_img");
	make_dir($basepath."/".$imgpath."/source_img");
	make_dir($basepath."/".$imgpath."/thumb_img");
	$bimg = $imgpath."/goods_img/".randomkeys(12).$ext;
	$simg = $imgpath."/source_img/".$filename.$ext;
	$timg = $imgpath."/thumb_img/".randomkeys(12).$ext;
	
	if (!$isremote){
		make_dir($basepath."/images/upload/".$orgpath);
		//远程下载图片
		//$orgimg1 = GrabImage($imgurl,$basepath."/images/upload/".$orgimg);
		if (strpos($imgurl,"http://")===false){
			$orgimg1 = $basepath.$imgurl;
		}else{
			$orgimg1 = GrabImage($imgurl,$basepath."/images/upload/".$orgimg);
		}
		
		//打水印
		$watermark = str_replace("../","",$GLOBALS['_CFG']['watermark']);
		if ((!empty($watermark )) && ($GLOBALS['_CFG']['watermark_place'] > 0) && ($GLOBALS['image']->validate_image($watermark ))){
			$orgimg1 = $GLOBALS['image']->add_watermark($orgimg1, '', $watermark , $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
		}
		//缩微图
		if (!compressImg($orgimg1,$tw,$th,1,$basepath."/".$timg)){
			//压缩不成功，则返回失败
			return false;
		}
		//商品图图
		// if (!compressImg($orgimg1,$iw,$ih,1,$basepath."/".$bimg)){
		//	压缩不成功，则返回失败
			// return false;
		// }
		copy($orgimg1,$basepath."/".$bimg);
		copy($orgimg1,$basepath."/".$simg);
	}else{
		$bimg = $imgurl;
		$simg = $imgurl;
		$timg = $imgurl;
	}
	$uptime = time();
	//插入数据库 
	$goods_gallery = array();
	$goods_gallery['goods_id'] = $gid;
	$goods_gallery['img_original']=$simg;
	$goods_gallery['img_url']=$bimg;
	$goods_gallery['thumb_url']=$timg;
	$db->autoExecute($ecs->table('goods_gallery'), $goods_gallery, 'INSERT');
	
	if ($isdefault){
		//商品图
		if (!$isremote){
			$gimg = $imgpath."/goods_img/".randomkeys(12).$ext;
			if (!compressImg($orgimg1,$iw,$ih,1,$basepath."/".$gimg)){
				//压缩不成功，则返回失败
				return false;
			}
		}else{
			$gimg = $imgurl;
			if (isset($GLOBALS["listpic"]) && $GLOBALS["listpic"]!=""){
				$timg = $GLOBALS["listpic"];
			}
		}
		$db->query("UPDATE " . $ecs->table('goods') . " SET goods_img = '$gimg', goods_thumb = '$timg', original_img = '$simg' WHERE goods_id='$gid'");
	}
	//unlink($orgimg1);
	return true;
}

function generate_goods_sn($goods_id)
{
	global $db;
	
    $goods_sn = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

    $sql = "SELECT goods_sn FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " .
            " ORDER BY LENGTH(goods_sn) DESC";
    $sn_list = $db->getCol($sql);
    if (in_array($goods_sn, $sn_list))
    {
        $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
        $new_sn = $goods_sn . mt_rand(0, $max);
        while (in_array($new_sn, $sn_list))
        {
            $new_sn = $goods_sn . mt_rand(0, $max);
        }
        $goods_sn = $new_sn;
    }

    return $goods_sn;
}

function getcatid($catpath){
	global $db;
	global $ecs;
	$pid = "0";
	$cid = "0";
	$cats = explode(":",$catpath);
	foreach ($cats AS $cat){
		if (!empty($cat) && $cat!=""){
			$lastcatname = $cat;
			$sql = "SELECT cat_id FROM " . $ecs->table('category')." where cat_name='$cat' and parent_id=$pid";
			$res=$db->query($sql);
			$row = $db->fetchRow($res);
			if (!empty($row))
			{
				$cid = $row["cat_id"];
				$pid = $cid;
			}
			else
			{
				$sql = "INSERT INTO " . $ecs->table('category') . " (cat_name,parent_id,style) VALUES ('" . addslashes($cat) . "','".$pid."','')";
				$db->query($sql);
				$cid = $db->insert_id();
				$pid = $cid;
			}
		}
	}
	return $cid;
}

function getsupid($supname){
	global $db;
	global $ecs;
	
	$sql = "SELECT suppliers_id, suppliers_name FROM " . $ecs->table('suppliers')." where suppliers_name='$supname'";
	$res = $db->query($sql);
	$res=$db->query($sql);
	$row = $db->fetchRow($res);
	if (!empty($row))
	{
		$supid = $row["suppliers_id"];
	}
	else
	{
		$sql = "INSERT INTO " . $ecs->table('suppliers') . " (suppliers_name,suppliers_desc,is_check) VALUES ('" . addslashes($supname) . "','',1)";
		$db->query($sql);
		$supid = $db->insert_id();
	}
	return $supid;
}

function addExtCat($gid,$cateid){
	
}

$act = $_GET["action"];
if ($act=="post"){
	//发布商品
	extract ( $_POST, EXTR_OVERWRITE ); // 格式化post参数
	
	if (! $catname && !$cat_id){ exit( "分类不正确，必须选择ID，或者设置分类路径" );}
	if (! $goods_name) { exit ( "标题不能为空" ); }
	if (! $shop_price) { exit ( "价格不能为空" ); }
	if (! $goods_desc) { exit ( "内容不能为空" ); }
	
	//检测编号重复的商品
	$sql = "select * from ".$ecs->table('goods')." where `goods_sn` = '$goods_sn'";
	$row = $db->getRow($sql);
	if ($row!==false){
		//编号重复的时候，自动做扩展类目处理
		$repgid = $row["goods_id"];
		 $maincid = $row["cat_id"];
		 $newcid = getcatid($catname);
		//类目不同则考虑添加为扩展类目
		 if ($maincid!=$newcid){
		//	检测是否已经关联该扩展类目
			 $gcount = $db->getOne("select count(*) from ".$ecs->table('goods_cat')." where `goods_id` = '$repgid' and `cat_id`='$newcid'");
			 if ($gcount==0){
				 $sql = "INSERT INTO " . $ecs->table('goods_cat') . " (goods_id,cat_id) VALUES ('$repgid','$newcid')";
				 $db->query($sql);
			 }
		 }
		exit("商品发布成功，编号重复");
		//删除已经存在的商品信息
		$db->query("delete from " . $ecs->table('goods')." where goods_id=".$repgid);
		$db->query("delete from " . $ecs->table('goods_gallery')." where goods_id=".$repgid);
		$db->query("delete from " . $ecs->table('goods_attr')." where goods_id=".$repgid);
		$db->query("delete from " . $ecs->table('goods_cat')." where goods_id=".$repgid);
	}
	
	/* 字段默认值 */
	$default_value = array(
		'brand_id'      => 0,
		'goods_number'  => 0,
		'goods_weight'  => 0,
		'market_price'  => 0,
		'shop_price'    => 0,
		'warn_number'   => 0,
		'is_real'       => 1,
		'is_on_sale'    => 1,
		'is_alone_sale' => 1,
		'integral'      => 0,
		'is_best'       => 0,
		'is_new'        => 0,
		'is_hot'        => 0,
		'goods_type'    => 0,
	);

	/* 查询品牌列表 */
	$brand_list = array();
	$cat_list = array();
	$sql = "SELECT brand_id, brand_name FROM " . $ecs->table('brand');
	$res = $db->query($sql);
	while ($row = $db->fetchRow($res))
	{
		$brand_list[$row['brand_name']] = $row['brand_id'];
	}

	/* 字段列表 */
	$field_list = array("cat_id","goods_name","goods_sn","brand_name","market_price","shop_price","integral","keywords",
						"goods_brief","goods_desc","goods_weight","goods_number","warn_number","is_best","is_new",
						"is_hot","is_on_sale","is_alone_sale","is_real","gallery","catname","attr","suppliers_id");
	$field_list[] = 'goods_class'; //实体或虚拟商品

	/* 获取商品good id */
	$max_id = $db->getOne("SELECT MAX(goods_id) + 1 FROM ".$ecs->table('goods'));
	// 合并
	$field_arr = array(
		'add_time'      => gmtime(),
		'last_update'   => gmtime(),
	);
	
	//检查是否有符合要求的价格变量
	if (isset($pricevar) && is_numeric($pricevar)){
		$pricemul = floatval($pricevar);
	}else{
		$pricemul = 1;
	}
	
	foreach ($field_list AS $field)
	{
		// 转换编码
		$field_value = isset($_POST[$field]) ? $_POST[$field] : '';

		/* 虚拟商品处理 */
		if ($field == 'goods_class')
		{
			$field_value = intval($field_value);
			if ($field_value == G_CARD)
			{
				$field_arr['extension_code'] = 'virtual_card';
			}
			continue;
		}

		// 如果字段值为空，且有默认值，取默认值
		$field_arr[$field] = !isset($field_value) && isset($default_value[$field]) ? $default_value[$field] : $field_value;

		// 特殊处理
		if (!empty($field_value))
		{
			// 图片路径
			if (in_array($field, array('original_img', 'goods_img', 'goods_thumb')))
			{
				if(strpos($field_value,'|;')>0)
				{
					$field_value=explode(':',$field_value);
					$field_value=$field_value['0'];
					@copy(ROOT_PATH.'images/'.$field_value.'.tbi',ROOT_PATH.'images/'.$field_value.'.jpg');
					if(is_file(ROOT_PATH.'images/'.$field_value.'.jpg'))
					{
						$field_arr[$field] =IMAGE_DIR . '/' . $field_value.'.jpg';
					}
				}
				else
				{
					$field_arr[$field] = IMAGE_DIR . '/' . $field_value;
				}
			  }
			// 品牌
			elseif ($field == 'brand_name')
			{
				if (isset($brand_list[$field_value]))
				{
					$field_arr['brand_id'] = $brand_list[$field_value];
				}
				else
				{
					$sql = "INSERT INTO " . $ecs->table('brand') . " (brand_name,brand_logo,brand_desc,site_url) VALUES ('" . addslashes($field_value) . "','$brand_img','" . addslashes($brand_desc) . "','$brand_site')";
					$db->query($sql);
					$brand_id = $db->insert_id();
					$brand_list[$field_value] = $brand_id;
					$field_arr['brand_id'] = $brand_id;
				}
			}
			// 整数型
			elseif (in_array($field, array('goods_number', 'warn_number', 'integral')))
			{
				$field_arr[$field] = intval($field_value);
			}
			// 数值型
			elseif (in_array($field, array('goods_weight', 'market_price', 'shop_price')))
			{
				$field_arr[$field] = floatval($field_value);
				//重量处理
				if ($field=="goods_weight"){
					if ($weight_unit=="G")	$field_arr[$field] = floatval($field_value)/1000;
				}
				//价格处理
				if ($field=="shop_price"){
					$field_arr[$field] = floatval(floatval($field_value)*$pricemul);
				}
				//市场价格处理
				if ($field=="market_price"){
					if (!$field_arr[$field]){
						$field_arr[$field] = floatval(floatval($field_value)*$pricemul);
					}
				}
			}
			// bool型
			elseif (in_array($field, array('is_best', 'is_new', 'is_hot', 'is_on_sale', 'is_alone_sale', 'is_real')))
			{
				$field_arr[$field] = intval($field_value) > 0 ? 1 : 0;
			}
		}
	}

	//如果上传了分类，则使用该分类
	$lastcatname = "";	//用了作为类型名称，设定商品属性
	if (!empty($field_arr['catname'])){
		$field_arr['cat_id']=getcatid($field_arr['catname']);
		$tcats = explode(":",$field_arr['catname']);
		$lastcatname=reset($tcats);

	}else{
		$lastcatname = "默认类型";
	}

	if (empty($field_arr['goods_sn']))
	{
		$field_arr['goods_sn'] = generate_goods_sn($max_id);
	}

	/* 如果是虚拟商品，库存为0 */
	if ($field_arr['is_real'] == 0)
	{
		$field_arr['goods_number'] = 0;
	}

	//替换商品详情的"
	$field_arr['goods_desc'] = str_replace('|“|','"',$field_arr['goods_desc']);
	
	//如果有修改系统，添加了扩展字段的话
	if (isset($exts)){
		foreach ($exts AS $key=>$value){
			$field_arr[$key] = $value;
			echo($key.":".$value);
		}
	}
	
	//配置供应商
	if (isset($suppliers) && $suppliers!=""){
		$field_arr['suppliers_id'] = getsupid($suppliers);
	}else{
		$field_arr['suppliers_id'] = 0;
	}
	
	$db->autoExecute($ecs->table('goods'), $field_arr, 'INSERT');
	$gid = $db->insert_id();
	//设置商品相册图
	if (!empty($field_arr['gallery']))
	{
		if (isset($remotepic) && $remotepic){	//判断是否需要远程下载图片
			$isremote = true;
		}else{
			$isremote = false;
		}
		$gallerys = explode(";",$field_arr['gallery']);
		for($i=0,$l=count($gallerys); $i<$l; $i++) {
			$gpic = $gallerys[$i];
			if (!addimage($gid,$gpic,!$i,$isremote) && $i==0)
			{
				//如果没有下载图片成功，则删除商品，并返回失败信息
				$db->query("delete from ".$ecs->table('goods')." where goods_id=".$gid);
				exit("图片下载失败");
			}
		}
	}
	
	//添加商品属性，如果存在商品属性，则需要为商品添加
	if (isset($goods_color) || isset($goods_size) || isset($goods_props) || isset($goods_protos)){
		$catid = "";
		$res=$db->query("SELECT cat_id FROM ".$ecs->table('goods_type')." where cat_name='$lastcatname'");
		$row = $db->fetchRow($res);
		if (!empty($row))
		{
			$catid = $row["cat_id"];
		}
		else
		{
			$sql = "INSERT INTO " . $ecs->table('goods_type') . " (cat_name) VALUES ('$lastcatname')";
			$db->query($sql);
			$catid = $db->insert_id();
		}
		//更新商品的类型
		//修改商品类型
		$db->query("UPDATE " . $ecs->table('goods') . " SET goods_type = '$catid' WHERE goods_id='$gid'");
		$guige_color = array();
		$guige_size =  array();
		//处理固定的颜色
		if (!empty($goods_color)){
			//先检测是否存在颜色，不存在要建立
			$att_color = "";
			$res=$db->query("SELECT attr_id FROM ".$ecs->table('attribute')." where attr_name='颜色' and cat_id='$catid'");
			$row = $db->fetchRow($res);
			if (!empty($row))
			{
				$att_color = $row["attr_id"];
			}
			else
			{
				$sql = "INSERT INTO " . $ecs->table('attribute') . " (cat_id,attr_name,attr_input_type,attr_type,attr_values) VALUES ('$catid','颜色','0','1','')";
				$db->query($sql);
				$att_color = $db->insert_id();
			}
			
			//插入颜色的属性值
			$colors = explode(";",$goods_color);
			foreach ($colors as $color){
				if ($color!=""){
					if (stripos($color,":")===false){
						$colorprice = 0;
						$colorvalue = $color;
					}else{
						$colorpv = explode(":",$color);
						$colorvalue = $colorpv[0];
						if (stripos($colorpv[1],"+")!==false || stripos($colorpv[1],"-")!==false){
							//如果是加价
							$colorprice  = $colorpv[1];
						}else if(stripos($colorpv[1],"x")!==false){
							//如果是按倍率来加
							$cliprice = str_replace('x','',$colorpv[1]);
							if ($cliprice>1){
								$colorprice = ($cliprice-1)*$shop_price;
							}else{
								$colorprice = $cliprice*$shop_price;
							}
						}else{
							$colorprice = $colorpv[1]-$shop_price;
						}
					}
					$sql = "INSERT INTO " . $ecs->table('goods_attr') . " (goods_id,attr_id,attr_value,attr_price) VALUES ('$gid','$att_color','$colorvalue','$colorprice')";
					$db->query($sql);
					$guige_color[] = $db->insert_id();
				}
			}
		}
		
		//再处理固定的尺寸
		if (!empty($goods_size)){
			//先检测是否存在尺寸，不存在要建立
			$att_size = "";
			$res=$db->query("SELECT attr_id FROM ".$ecs->table('attribute')." where attr_name='尺码' and cat_id='$catid'");
			$row = $db->fetchRow($res);
			if (!empty($row))
			{
				$att_size = $row["attr_id"];
			}
			else
			{
				$sql = "INSERT INTO " . $ecs->table('attribute') . " (cat_id,attr_name,attr_input_type,attr_type,attr_values) VALUES ('$catid','尺码','0','1','')";
				$db->query($sql);
				$att_size = $db->insert_id();
			}
			
			//插入尺寸的属性值,允许带价格
			$sizes = explode(";",$goods_size);
			foreach ($sizes as $size){
				if ($size!=""){
					if (stripos($size,":")===false){
						$sizeprice = 0;
						$sizevalue = $size;
					}else{
						$sizepv = explode(":",$size);
						$sizevalue = $sizepv[0];
						if (stripos($sizepv[1],"+")!==false || stripos($sizepv[1],"-")!==false){
							//如果是加价
							$sizeprice  = $sizepv[1];
						}else if(stripos($sizepv[1],"x")!==false){
							//如果是按倍率来加
							$cliprice = str_replace('x','',$sizepv[1]);
							if ($cliprice>1){
								$sizeprice = ($cliprice-1)*$shop_price;
							}else{
								$sizeprice = $cliprice*$shop_price;
							}
						}else{
							$sizeprice = $sizepv[1]-$shop_price;
						}
					}
					$sql = "INSERT INTO " . $ecs->table('goods_attr') . " (goods_id,attr_id,attr_value,attr_price) VALUES ('$gid','$att_size','$sizevalue','$sizeprice')";
					$db->query($sql);
					$guige_size[] = $db->insert_id();
				}
			}
		}
		
		$pattids = array();
		print_r($guige_color);
		print_r($guige_size);
		if (count($guige_color)>0){
			if (count($guige_size)>0){
				foreach($guige_color as $gc){
					foreach($guige_size as $gs){
						$pattids[] = $gc."|".$gs;
					}
				}
			}else{
				foreach($guige_color as $gc){
					$pattids[] = $gc;
				}
			}
		}else{
			if (count($guige_size)>0){
				foreach($guige_size as $gs){
					$pattids[] = $gs;
				}
			}
		}
		
		$i =0;
		if (count($pattids)>0){
			print_r($pattids);
			foreach($pattids as $paid){
				$i++;
				$product_sn = $goods_sn."-".$i;
				$sql = "INSERT INTO " . $ecs->table('products') . " (goods_id,goods_attr,product_sn,product_number) VALUES ('$gid','$paid','$product_sn','99')";
				$db->query($sql);
			}
		}
		
		//处理固定的规格
		if (!empty($goods_protos)){
			//先检测是否存在规格，不存在要建立
			$att_proto = "";
			$res=$db->query("SELECT attr_id FROM ".$ecs->table('attribute')." where attr_name='规格' and cat_id='$catid'");
			$row = $db->fetchRow($res);
			if (!empty($row))
			{
				$att_proto = $row["attr_id"];
			}
			else
			{
				$sql = "INSERT INTO " . $ecs->table('attribute') . " (cat_id,attr_name,attr_input_type,attr_type,attr_values) VALUES ('$catid','规格','0','1','')";
				$db->query($sql);
				$att_proto = $db->insert_id();
			}
			
			//插入规格的属性值
			//规格格式要求：
			//货号:规格:价格
			//如果货号为$SN，则调用系统编号自动递增
			$protos = explode(";",$goods_protos);
			$pi = 0;
			foreach ($protos as $proto){
				
				if ($proto!=""){
					$pi++;
					$provalues = explode(":",$proto);
					if (count($provalues)==3){
						if ($provalues[0]!="$SN"){
							$proSn = $provalues[0];
						}else{
							$proSn = $goods_sn."-".$pi;
						}
						$proValue = $provalues[1];
						$proPrice = $provalues[2]-$shop_price;
						
						$sql = "INSERT INTO " . $ecs->table('goods_attr') . " (goods_id,attr_id,attr_value,attr_price) VALUES ('$gid','$att_proto','$proValue','$proPrice')";
						$db->query($sql);
						$aproid = $db->insert_id();
						
						//插入货号
						$sql = "INSERT INTO " . $ecs->table('products') . "(goods_id,goods_attr,product_sn,product_number) VALUES ('$gid','$aproid','$proSn','500')";
						$db->query($sql);
					}
				}
			}
		}
		
		//最后处理属性
		if (!empty($goods_props)){
			//转换数据
			if (strpos($goods_props,",}")) 
				$goods_props = str_replace(",}","}",$goods_props);
			$goods_props = str_replace('\"','"',$goods_props);
			//$goods_props = iconv('gbk', 'utf-8', $goods_props);
			$props = json_decode($goods_props,TRUE);
			//遍历插入属性及属性值
			foreach($props AS $key=>$value){
				if ($value!=""){
					$att_id = "";
					$key=urldecode($key);
					$res=$db->query("SELECT attr_id FROM ".$ecs->table('attribute')." where attr_name='$key' and cat_id='$catid'");
					$row = $db->fetchRow($res);
					
					if (!empty($row))
					{
						$att_id = $row["attr_id"];
					}
					else
					{
						$sql = "INSERT INTO " . $ecs->table('attribute') . " (cat_id,attr_name,attr_input_type,attr_type,attr_values) VALUES ('$catid','$key','0','0','')";
						$db->query($sql);
						$att_id = $db->insert_id();
					}
					//处理多选属性的
					if (strpos($value,";")===false){
						$value = urldecode($value);
						$sql = "INSERT INTO " . $ecs->table('goods_attr') . " (goods_id,attr_id,attr_value,attr_price) VALUES ('$gid','$att_id ','$value','0')";
						$db->query($sql);
					}else{
						//如果包含多个属性值，则要批量插入，并修改为多选
						$varrs =  explode(";",$value);
						foreach($varrs as $varr){
							if ($varr!=""){
								$vtitle ="";
								$vapric ="0";
								//处理价格
								if (strpos($varr,":")===false){
									$vtitle = $varr;
								}else{
									$vaps =  explode(":",$varr);
									$vtitle =	$vaps[0];
									$vapric =	$vaps[1];
								}
								$sql = "INSERT INTO " . $ecs->table('goods_attr') . " (goods_id,attr_id,attr_value,attr_price) VALUES ('$gid','$att_id ','$vtitle','$vapric')";
								$db->query($sql);
								/*--//特别处理，增加为扩展分类
								$ecid = getcatid("按".$key."选".$lastcatname.":".$varr);
								$sql = "INSERT INTO " . $ecs->table('goods_cat') . " (goods_id,cat_id) VALUES ('$gid','$ecid')";
								$db->query($sql);
								--*/
							}
						}
						$sql = "update ". $ecs->table('attribute') ." set attr_type=2 where attr_id=".$att_id;
						$db->query($sql);
					}
				}
			}
		}
	}
	
	//处理扩展分类
	if (isset($goods_extends)){
		$extcats = explode(";",$goods_extends);
		foreach($extcats as $ecat){
			$ecid = getcatid($ecat);
			$sql = "INSERT INTO " . $ecs->table('goods_cat') . " (goods_id,cat_id) VALUES ('$gid','$ecid')";
			$db->query($sql);
		}
	}
	
	//处理优惠价格
	if (isset($volume_price)){
		$volprices = explode(";",$volume_price);
		foreach($volprices as $vprice){
			$volset = explode(":",$vprice);
			$volnum = $volset[0];
			$volpri = $volset[1];
			$volpri = intval(floatval($volpri)*$pricemul);
			$sql = "INSERT INTO " . $ecs->table('volume_price') . " (price_type,goods_id,volume_number,volume_price) VALUES ('1','$gid','$volnum','$volpri')";
			$db->query($sql);
		}
	}
	
	echo("商品发布成功|Success");
}else{
	//获取分类
	$sql = "select cat_id,parent_id,cat_name from ".$ecs->table('category');
	$rs = $db->query($sql);
	if(!$rs){die("valid result!");}
	echo("<select>");
	while($row = $db->fetchRow($rs)){
		if($row["parent_id"]==0){
			echo "<option value='".$row["cat_id"]."'>".$row["cat_name"]."</option>";
		}else{
			echo "<option value='".$row["cat_id"]."'>----".$row["cat_name"]."</option>";
		}
	}
	echo("</select>");
}

?>