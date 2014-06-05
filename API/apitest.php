<?php

	//Getting the filters
	//Picture size: icon, small, large, huge (default)
	if(isset($_POST["size"]))
		$str_Size = $_POST["size"];
	else
		$str_Size = "huge";
	
	//Picture type: jpg (default), png, gif
	if(isset($_POST["type"]))
		$str_Type = $_POST["type"];
	else
		$str_Type = "jpg";

	//Picture keywords or tags
	if(isset($_POST["keywords"]) && $_POST["keywords"] != "")
		$str_Post_Keywords = $_POST["keywords"];
	else
		$str_Post_Keywords = "";

	//This function gets a url content as a string. It could be HTML, JSON, XML, CSS, or a simple TXT.
	function get_url_contents($str_URL) {
	    $obj_CRL = curl_init();
	    curl_setopt($obj_CRL, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
	    curl_setopt($obj_CRL, CURLOPT_URL, $str_URL);
	    curl_setopt($obj_CRL, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($obj_CRL, CURLOPT_CONNECTTIMEOUT, 5);

	    $str_Result = curl_exec($obj_CRL);
	    curl_close($obj_CRL);
	    return $str_Result;
	}

	//if there are keywords to search for
	if($str_Post_Keywords != ""){
		//split keywords in case there are more than one
		$arr_Keywords = explode(" ", $str_Post_Keywords);
		$int_Key_Count = count($arr_Keywords);

		//GOOGLE custom search (images)
		//Initialization block
		$str_Google_Images = "";
		$str_API_Key = "AIzaSyByBdy9SpWiRkMni7Nlr3fgkWungLCpelg";
		$str_API_CX = "*";//014492187973190243563:cmpypixvfz8";
		$int_Const_Limit = 4;
		$str_RootUri = 'https://ajax.googleapis.com/ajax/services/search/images?v=1.0';
		foreach($arr_Keywords as $str_){
			$int_C = 0;
			$int_Temp_Limit = floor($int_Const_Limit / $int_Key_Count);
			//Preparing the query string
		    $str_Keyword = urlencode("'{$str_}'");
		    $str_Query = $str_RootUri . "&q=" . $str_Keyword . "&imgsz=" . $str_Size . "&as_filetype=" . $str_Type . "&r=" . time();// . "&key=" . $str_API_Key . "&cx=" . $str_API_CX

		    //Getting query result as json string
		    $str_JSON = get_url_contents($str_Query);
		    //Parsing json string to a php array
		    $arr_JSON = json_decode($str_JSON);
		    //If there are query results
		    if(is_array($arr_JSON->responseData->results) && count($arr_JSON->responseData->results) > 0){
			    foreach ($arr_JSON->responseData->results as $obj_Result) {
			        //Add a new result picture to this column
			        $str_Google_Images .= "<div class=\"cls_Clear\"></div>
			        					<div class=\"cls_Thumbnail\">
			        						<div style=\"float:left;\"><a href=\"" . $obj_Result->url . "\"><img src=\"". $obj_Result->url . "\" class=\"cls_Thumbnail_Picture\"></a></div>
											<div class=\"cls_Thumbnail_Label\">" . $obj_Result->title . "</div>
										</div>";
					//Whatching the limits
					$int_C++;
					if($int_C >= $int_Temp_Limit)
						break;
			    }
			}
		}

		//BING
		//Initialization block
		$str_Bing_Images = "";
		$str_acctKey = 'wVQp2RsabIDW4vEHbwqKJ9x56xG+itad285Pv7eKOPg'; //OC9JGaXdBsyW50Bb9/q0R9xskDQzA6oHghtPHA254bY='; //bladgack main pixellet
		$obj_Auth = base64_encode("$str_acctKey:$str_acctKey");
		$int_Const_Limit = 1;
		$str_RootUri = 'https://api.datamarket.azure.com/Bing/Search';
		switch($str_Size){
			case "huge": $str_Bing_Size = "Large"; break;
			case "large": $str_Bing_Size = "Medium"; break;
			case "small": $str_Bing_Size = "Small"; break;
			case "icon": $str_Bing_Size = "Small"; break;
		}
		foreach($arr_Keywords as $str_){
			$int_C = 0;
			$int_Temp_Limit = floor($int_Const_Limit / $int_Key_Count);

			//Preparing the query string
			$str_Keyword = urlencode("'{$str_}'");
			$str_Query = $str_RootUri . "/Image?\$format=json&Query=" . $str_Keyword;// . "&r=" . time(); // . "&Image.Filters=Size:" . $str_Bing_Size . "";//&WebFileType=" . $str_Type
			
			$arr_get_context = array(
				'http' => array(
				'request_fulluri' => true,
				'ignore_errors' => true,
				'header' => "Authorization: Basic $obj_Auth")
			);
			//Creating a Stream Context (Required for this API)
			$obj_context = stream_context_create($arr_get_context);
			//Getting query result as json string
			$str_JSON = file_get_contents($str_Query, 0, $obj_context);

			//Parsing json string to a php array
			$arr_JSON = json_decode($str_JSON); 
			//If there are query results
			if(is_array($arr_JSON->d->results) && count($arr_JSON->d->results) > 0){
				foreach($arr_JSON->d->results as $obj_Result) { 
					//Add a new result picture to this column
					$str_Bing_Images .= "<div class=\"cls_Clear\"></div>
			        					<div class=\"cls_Thumbnail\">
			        						<div style=\"float:left;\"><a href=\"" . $obj_Result->MediaUrl . "\"><img src=\"". $obj_Result->Thumbnail->MediaUrl . "\" class=\"cls_Thumbnail_Picture\"></a></div>
											<div class=\"cls_Thumbnail_Label\">" . $obj_Result->Title . "</div>
										</div>";
					//Whatching the limits
					$int_C++;
					if($int_C >= $int_Temp_Limit)
						break;
				}
			}
		}

		//FLICKR
		//Initialization block
		$str_Flickr_Images = "";
		$str_API_Key = "c2c518a02303a4a0ff55cd9fa2503573";
		$str_API_NSID = "124673575@N04";
		$int_Const_Limit = 4;

		//Required class for this API
		require_once("phpFlickr.php"); 
		//Initialyzing object
  		$obj_Flickr = new phpFlickr($str_API_Key); 
  		$str_RootUri    = "http://www.flickr.com/photos/" . $str_API_NSID . "/";

		foreach($arr_Keywords as $str_){
			$int_C = 0;
			$int_Temp_Limit = floor($int_Const_Limit / $int_Key_Count);

		    //Preparing the query string
		    $str_Keyword = rawurlencode($str_);
		    $arr_Photos = $obj_Flickr->photos_search(array("tags"=> $str_Keyword, "user_id"=> $str_API_NSID, "sort"=>"date-posted-desc", "privacy_filter"=>"1", "per_page"=>$int_Const_Limit));

		    //If there are query results
		    if(is_array($arr_Photos['photo']) && count($arr_Photos['photo']) > 0){
			    foreach ($arr_Photos['photo'] as $obj_Result) {
			        //Add a new result picture to this column
			        $str_Flickr_Images .= "<div class=\"cls_Clear\"></div>
			        					<div class=\"cls_Thumbnail\">
			        						<div style=\"float:left;\"><a href=\"" . $str_RootUri . $obj_Result["id"] . "\">
												<img alt='".$obj_Result['title']."' title='".$obj_Result['title']."' "."src='".$obj_Flickr->buildPhotoURL($obj_Result, "square")."' class=\"cls_Thumbnail_Picture\" />
			        						</div>
											<div class=\"cls_Thumbnail_Label\">" . $obj_Result["title"] . "</div>
										</div>";
					//Whatching the limits
					$int_C++;
					if($int_C >= $int_Temp_Limit)
						break;
			    }
			}
		}

		

	}

	

?>
<html>
	<head>
		<style>
			.cls_Thumbnails_Column{
				width:360px;
				float: left;
				margin-right: 20px;
			}
			.cls_Thumbnails_Column_Label{
				vertical-align: top;
				background-color:#303030; 
				color:#ffffff;
				width:360px;
				height:20px;
				float:left;
			}
			.cls_Thumbnail{
				background-color:#aedd94;
				width:360px;
				height:300px;
				float:left;
				margin-bottom:30px;
				display: block;
			}
			.cls_Thumbnail_Picture{
				width: 360px;
				height: 280px;
				border: 0px;
			}
			.cls_Thumbnail_Label{
				vertical-align: bottom;
				background-color:#606060; 
				color:#aec0c0;
				width:360px;
				height:20px;
				float:left;
			}
			.cls_Clear{
				clear:both;
			}
		</style>
	</head>
	<body style="background-color:#000000;">
		<div style="float:left; width:1550px;">
			<div style="float:left; width:100%; height: 80px; background-color:#d6d6d6; text-align:center;">
				<br>
				<form action="apitest.php" method="post">
					<input type="text" name="keywords" value="<?php echo $str_Post_Keywords ?>">
					<select name="size">
						<option value="huge" <?php echo ($_POST["size"] == "huge") ? "selected" : ""; ?>>Huge</option>
						<option value="large" <?php echo ($_POST["size"] == "large") ? "selected" : ""; ?>>Large</option>
						<option value="small" <?php echo ($_POST["size"] == "small") ? "selected" : ""; ?>>Small</option>
						<option value="icon" <?php echo ($_POST["size"] == "icon") ? "selected" : ""; ?>>Icon</option>
					</select>
					<select name="type">
						<option value="jpg" <?php echo ($_POST["type"] == "jpg") ? "selected" : ""; ?>>Jpg</option>
						<option value="png" <?php echo ($_POST["type"] == "png") ? "selected" : ""; ?>>Png</option>
						<option value="gif" <?php echo ($_POST["type"] == "gif") ? "selected" : ""; ?>>Gif</option>
					</select>
					<input type="submit" value="Submit">
				</form>
			</div>
			<div style="float:left; width:100%; background-color:#444444;">
				<div>
					<div style="background-color:#aaaaaa; color:#ffffff;">Images sources:</div>
					<div style="background-color:#ffffff;">
						<div class="cls_Thumbnails_Column">
							<div class="cls_Thumbnails_Column_Label">Google Images</div>
							<?php echo $str_Google_Images; ?>
						</div>
						<div class="cls_Thumbnails_Column">
							<div class="cls_Thumbnails_Column_Label">Bing Images</div>
							<?php echo $str_Bing_Images; ?>
						</div>
						<div class="cls_Thumbnails_Column">
							<div class="cls_Thumbnails_Column_Label">Flickr Images</div>
							<?php echo $str_Flickr_Images; ?>
						</div>
						<div class="cls_Thumbnails_Column">
							<div class="cls_Thumbnails_Column_Label">Yahoo Images</div>
							<?php echo $str_Yahoo_Images; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>