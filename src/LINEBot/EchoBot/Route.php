<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */




namespace LINE\LINEBot\EchoBot;

use PDO;

use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\Exception\UnknownEventTypeException;
use LINE\LINEBot\Exception\UnknownMessageTypeException;




class Route
{
    
    
    public function register(\Slim\App $app)
    {
        $app->post('/callback', function (\Slim\Http\Request $req, \Slim\Http\Response $res) {
            $qq="";
            $db_host = 'db.mis.kuas.edu.tw';
        	$db_name = 's1104137117';
        	$username = 's1104137117';
        	$password = 'op0963459914';
        	$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
        	$db = new PDO($dsn, $username,$password);
        	

            /** @var \LINE\LINEBot $bot */
            $bot = $this->bot;
            /** @var \Monolog\Logger $logger */
            $logger = $this->logger;

            $signature = $req->getHeader(HTTPHeader::LINE_SIGNATURE);
            if (empty($signature)) {
                return $res->withStatus(400, 'Bad Request');
            }

            // Check request with signature and parse request
            try {
                $events = $bot->parseEventRequest($req->getBody(), $signature[0]);
            } catch (InvalidSignatureException $e) {
                return $res->withStatus(400, 'Invalid signature');
            } catch (UnknownEventTypeException $e) {
                return $res->withStatus(400, 'Unknown event type has come');
            } catch (UnknownMessageTypeException $e) {
                return $res->withStatus(400, 'Unknown message type has come');
            } catch (InvalidEventRequestException $e) {
                return $res->withStatus(400, "Invalid event request");
            }

            foreach ($events as $event) {
    	// Postback Event
    	if (($event instanceof \LINE\LINEBot\Event\PostbackEvent)) {
    		$logger->info('Postback message has come');
    		$event_data = $event->getPostbackData();
    		
    		$method=explode("&",$event_data);
    		
            
    		
    		switch($method[0])
    		{
    		    case "lackoff":
    		        $multipleMessageBuilder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
                    date_default_timezone_set('Asia/Taipei');
                    $date= (int)date(Y);
                    for($i=1;$i<=12;$i++){
                        if($method[1]==$i){
                             $query = $db -> query("SELECT datetime,student_leave,student_late FROM student_score  where student_id='$method[2]' and datetime like '$date-0$method[1]%'");
    		                 $lackoff = $query -> fetchAll();
    		                 if($lackoff[0]!="")
    		                 {
        		                 foreach($lackoff as $row){
        		                     if($row['student_late']!=""){
        		                         if ($row['student_leave']!=""){
        		                            $multipleMessageBuilder->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('日期:'.$row[0].'狀態:'.$row[1]));
        		                         }else{
        		                             if ($row['student_late']!="到"){
        		                                 $multipleMessageBuilder->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('日期:'.$row[0].'狀態:'.$row[2]));
        		                             }
        		                         }
        		                     }
        		                 }
        		                 $outputText= $multipleMessageBuilder;
        		                 $a = true;
        		                 break;
    		                 }
    		                 else
    		                 {
    		                     $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("這個月沒資料喔~");
    		                     break;
    		                 }
                        }
                    }
    		    break;
    		    
    		    case "post":
    		        
    		        //method[2]=title;
    		        //method[3]=leader_id;
    		        $row=$db->query("SELECT `content` FROM `post_db` WHERE `leader_id`='".$method[3]."'"."and `title`='".$method[2]."'");
    		        $row1=$row->fetch();
    		       
    		        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($row1[0]);
    		        break;
    		    
    		    case "check":
    		        $profile_id = $event->getuserId();
    		        date_default_timezone_set('Asia/Taipei');
                    $date=  date ("Y-m-d"); 
                    $time = date("H:i");
    		        
    		        $student_id = $db->query("Select student_id From student_db Where line_student_id = '$profile_id'");
    		        $student_id = $student_id->fetch();
    		        
    		        $check_in_result = $db->query("Select * From student_db Where student_id='".$student_id['student_id']."' And datetime='".$date."'");
    		        $check_in_result = $check_in_result->fetch();
    		        
                    //按下點到按鈕
    		        if($method[1]=="fst")
    		        {
    		            //判斷是否點過名
    		            if($check_in_result['datetime']=="")
    		            {
        		            //判斷點到時間
        		            if(strtotime($time)-strtotime("07:30")>=0 && strtotime($time)-strtotime("07:35")<=0)
        		            {
        		                $db->exec("UPDATE `student_db` SET `checkin`='1', `datetime`='".$date."' WHERE (`student_id`='".$student_id['student_id']."')");
        		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("點到成功，記得點退喔");
        		            }
        		            else if(strtotime($time)-strtotime("08:00")<=0 && strtotime($time)-strtotime("07:35")>=0)
        		            {
        		                $db->exec("UPDATE `student_db` SET `checkin`='1',`late`='1', `datetime`='".$date."' WHERE (`student_id`='".$student_id['student_id']."')");
        		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("你遲到囉<3，但還是要記得點退");
        		            }
        		            else
        		            {
        		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("還沒到點名時間喔");
        		            }
    		            }
    		            else
    		            {
    		                if($check_in_result['checkout']==0)
    		                {
    		                    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("你還沒點退喔，要記得點退");
    		                }
    		                else
    		                {
    		                    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("要等明天才可以再點一次歐，太認真了ㄅ");
    		                }
    		            }
    		        }
    		        else
    		        {
    		            //判斷是否點過名
    		            if($check_in_result['datetime']!="")
    		            {
    		                //判斷是否點退過
    		                if($check_in_result['checkout']==0)
    		                {
            		            //判斷點到時間
            		            if(strtotime($time)-strtotime("07:50")>=0 && strtotime($time)-strtotime("08:00")<=0)
            		            {
            		                $db->exec("UPDATE `student_db` SET `checkout`='1' WHERE (`student_id`='".$student_id['student_id']."')");
            		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("成功點退");
            		            }
            		            else
            		            {
            		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("還沒到點退時間喔");
            		            }
    		                }
    		                else
    		                {
            		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("你已經點退過囉");
    		                }
    		            }
    		            else
    		            {
    		                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("請先點到");

    		            }
    		        }
    		    break;
    		    case "leave":
    		        $date= date("Y-m-d");
    		        $line_user_id = $event->getuserId();
    		        if($method[1]=="a"){
    		            $db->exec("UPDATE `student_db` SET `student_leave`='事假' WHERE `student_id`='$method[2]' and  `datetime`='$date'");
            	        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($method[2]."請事假成功");
            	        $db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='$line_user_id'");
    		        }
    		        else if($method[1]=="b"){
    		            $db->exec("UPDATE `student_db` SET `student_leave`='病假' WHERE `student_id`='$method[2]' and  `datetime`='$date'");
            	        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($method[2]."請病假成功");
            	        $db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='$line_user_id'");
    		        }
    		        else if($method[1]=="c"){
    		            $db->exec("UPDATE `student_db` SET `student_leave`='公假' WHERE `student_id`='$method[2]' and  `datetime`='$date'");
            	        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($method[2]."請公假成功");
            	        $db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='$line_user_id'");
    		        }
    		        else if($method[1]=="d"){
    		            $db->exec("UPDATE `student_db` SET `student_leave`='喪假' WHERE `student_id`='$method[2]' and  `datetime`='$date'");
    		            $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($method[2]."請喪假成功");
    		            $db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='$line_user_id'");
    		        }
    		        
    		    break;
    		        if($method[1]=="a"){
    		            
    		        }
    		    default :
    		        
    		    break;
    		}
    		$response = $bot->replyMessage($event->getReplyToken(), $outputText);
    		continue;
    	}
    	// Location Event
    	if  ($event instanceof LINE\LINEBot\Event\MessageEvent\LocationMessage) {
    		$logger->info("location -> ".$event->getLatitude().",".$event->getLongitude());
    		continue;
    	}
    	// Message Event = TextMessage
    	
    	if (($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    	    $line_user_id = $event->getuserId();
    	    $leader = $db->query("SELECT `status` FROM `leader_db` WHERE `line_user_id`='$line_user_id'");
    	    $student = $db->query("SELECT `status` FROM `student_db` WHERE `line_user_id`='$line_user_id'");
    	    $a=0;
    	    if ($student!=""){
    	        $student = $student->fetch();
    	        $a=$a+$student[0];
    	    }else if ($leader!=""){
    	        $leader = $leader->fetch();
    	        $a=$a+$leader[0];
    	    }
    	    if($a==0){
        		$messageText=strtolower(trim($event->getText()));
        		switch ($messageText) {
        		case "text" : 
        			$outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("fuck");
        			break;
        		case "註冊":
        		    //抓出使用者ID
        		    $profile_id = $event->getuserId();
        		    $register_or_not = $db->query("Select line_student_id From student_db Where line_student_id ='$profile_id'");
        		    $register_or_not = $register_or_not->fetch();
        		    
                    //判斷是否註冊過
        		    if($register_or_not[0]==NULL)
        		    {
            		    $register_site = "http://fs.mis.kuas.edu.tw/~s1104137112/service_learning/register.php?user_id=".$profile_id;
            		    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($register_site);
        		    }
        		    else
        		    {
        		        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("你已經註冊過囉<3");
        		    }
        		    break;
        		case "location" :
        			$outputText = new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder("Eiffel Tower", "Champ de Mars, 5 Avenue Anatole France, 75007 Paris, France", 48.858328, 2.294750);
        			break;
        		case "點名" :
        		   $profile_id = $event->getuserId();
    		    
        		    $leader_or_not = $db->query("Select leader_id From leader_db Where line_user_id='$profile_id'");
        		    $leader_or_not = $leader_or_not->fetch();
        		    
        		    //判斷是否為小組長
        		    if($leader_or_not=="")
        		    {
        		        $actions = array (
        				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("點到", "check&fst"),
        				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("點退", "check&sec"),
            			);
            			$button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder("點名囉，各位同學", $actions);
            			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
        		    }
        		    else
        		    {
        		        date_default_timezone_set('Asia/Taipei');
                        $date= date("Y-m-d");
        		        
        		        $not_come = $db->query("Select student_id From student_db where ISNULL(datetime) And leader_id = '".$leader_or_not['leader_id']."'");
        		        $not_come = $not_come->fetchAll();
        		        
        		        if($not_come[0]=="")
        		        {
        		            $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("今天大家都很乖喔，都有來");
        		        }
        		        else
        		        {
            		        
            	            $test="";
            	            foreach($not_come as $student_id)
            	            {
            	                $test .= $student_id['student_id']."還沒有點名\r\n";
            	            }
                            
                            $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($test);
            		        
        		        }
        		    }
    			break;
        		case "nothing":
        		    
        		    $multipleMessageBuilder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
                    $multipleMessageBuilder->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('123', 'text2'))
                           ->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("456"))
                           ->add(new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder('https://translate.google.com/translate_tts?ie=UTF-8&q=%E5%AD%90%E5%8E%9A%E5%B9%B9%E4%BD%A0%E5%A8%98&tl=zh-CN&total=1&idx=0&textlen=5&tk=134524.283911&client=t&prev=input', 1000));
        		    $outputText =$multipleMessageBuilder;
        		    break;
        		case "button" :
        			$actions = array (
        				// general message action
        				New \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("button 1", "button"),
        				// URL type action
        				New \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder("Eyny", "http://fs.mis.kuas.edu.tw/~s1104137117/Eyny/"),
        				// The following two are interactive actions
        				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("next page", "page=3"),
        				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("Previous", "page=1")
        			);
        			$img_url = "https://thumbs.gfycat.com/WelloffSimilarAdmiralbutterfly-size_restricted.gif";
        			$button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("button text", "description", $img_url, $actions);
        			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
        			break;
        		case "其他" :
        			$actions = array (
        				// 出席分數
        				New \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("出席分數", "出席分數"),
        				// 結算
                        New \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("結算", "算帳"),
                        //伺服器時間
                        New \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("當前時間", "當前時間"),
                        
                        New \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("新增公告", "新增公告")
            
        			);
        			$img_url = "https://thumbs.gfycat.com/WelloffSimilarAdmiralbutterfly-size_restricted.gif";
        			$button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("button text", "description", $img_url, $actions);
        			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
        			break;
        		case "carousel" :
        			$columns = array();
        			$img_url = "https://cdn.shopify.com/s/files/1/0379/7669/products/sampleset2_1024x1024.JPG?v=1458740363";
        			for($i=0;$i<5;$i++) {
        				$actions = array(
        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("Add to Card","action=carousel&button=".$i),
        					new \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder("View","http://www.google.com")
        				);
        				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("Titl", "description", $img_url , $actions);
        				$columns[] = $column;
        			}
        			$carousel = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columns);
        			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("Carousel Demo", $carousel);
        			break;
        		case "當前時間":
        		    date_default_timezone_set('Asia/Taipei');
                    $date= date("H:i:s");
                    
                    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("(・ω・)づ  ".$date);
        		    break;
        		case "缺曠" :
        		    
        		    date_default_timezone_set('Asia/Taipei');
                    $date= (int)date(m);
                    $line_user_id = $event->getuserId();
                    $sql="select * from leader_db where `line_user_id`='$line_user_id'";
                	$rs = $db->query($sql);
                	$row = $rs->fetch();
                	if($row['leader_id']!=""){
                	    $db->exec("UPDATE `leader_db` SET `status`='2' WHERE `line_user_id`='$line_user_id'");
                	    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("輸入學號");
                	}
                	else{
                	    $sql="select * from student_db where `line_student_id`='$line_user_id'";
                	    $rs = $db->query($sql);
                	    $row = $rs->fetch();
                	    $db->exec("UPDATE `student_db` SET `status`='2' WHERE `line_student_id`='$line_user_id'");
                	    $columns = array();
            			for($i=1;$i<5;$i++) {
            			    switch($i){
            			        case 1:
            			            $img_url = "https://drscdn.500px.org/photo/2029587/m%3D900/746e95c2db4a7fb404b986bb71131cf4";
            			            $actions = array(
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("3月","lackoff&3&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("4月","lackoff&4&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("5月","lackoff&5&".$row[2])
                    				);
                    				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("春", " ", $img_url , $actions);
                    				$columns[] = $column;
                    				break;
                    			case 2:
                    			    $img_url = "https://drscdn.500px.org/photo/836088/m%3D900/7e0d9b919600bf121f83d6808be06d7f";
                    			    $actions = array(
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("6月","lackoff&6&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("7月","lackoff&7&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("8月","lackoff&8&".$row[2])
                    					
                    				);
                    				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("夏", " ", $img_url , $actions);
                    				$columns[] = $column;
                    				break;
                    			case 3:
                    			    $img_url = "https://i.ytimg.com/vi/Dr1HXfr69Wk/maxresdefault.jpg";
                    			    $actions = array(
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("9月","lackoff&9&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("10月","lackoff&10&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("11月","lackoff&11&".$row[2])
                    				);
                    				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("秋", " ", $img_url , $actions);
                    				$columns[] = $column;
                    				break;
                    			case 4:
                    			     $img_url = "https://cdn0-digiphoto-techbang.pixfs.net/system/images/115496/original/3a7e322ff5f5a1b8768e2ed595affd79.jpg?1478068542";
                    			     $actions = array(
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("12月","lackoff&12&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("1月","lackoff&1&".$row[2]),
                    					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("2月","lackoff&2&".$row[2])
                    				);
                    				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("冬", " ", $img_url , $actions);
                    				$columns[] = $column;
                    				break;
            			    }
            			}
            			$db->exec("UPDATE `student_db` SET `status`='0' WHERE `line_student_id`='$line_user_id'");
            			$carousel = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columns);
            			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("Carousel Demo", $carousel);
                	}
        			break;
        		case "image" :
        			$img_url = "https://thumbs.gfycat.com/WelloffSimilarAdmiralbutterfly-size_restricted.gif";
        			$outputText = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($img_url, $img_url);
        			break;
        		case "公告" :
        		           $line_student_id = $event->getuserId();
        			       $sql="select `leader_id` from `student_db` where `line_student_id`='".$line_student_id."'";
                    	   $rs = $db->query($sql);
                    	   $row = $rs->fetch();
                    	   $sql1="SELECT `title` FROM `post_db` WHERE `leader_id` ='".$row[0]."'ORDER by `post_id` DESC LIMIT 4";
                    	   $rs1 = $db->query($sql1);
                    	   $row1 = $rs1->fetchAll();
                    	   $c=0;
                    	   $key1="";$key2="";$key3="";$key4="";
                    	   foreach($row1 as $row2){
                    	        if($c==0){
                    	            $key1.=$row2['title'];}
                    	        else if($c==1){
                    	            $key2=$row2['title'];
                    	        }
                    	        else if($c==2){
                    	            $key3=$row2['title'];
                    	        }
                    	        else if($c==3){
                    	            $key4=$row2['title'];
                    	        }
                    	       $c=$c+1;
                    	   }
        			$actions = array ( 
        			            New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("公告1:'".$key1."'","post&a&".$key1."&".$row[0]),
        			            New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("公告2:'".$key2."'","post&b&".$key2."&".$row[0]),
        			            New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("公告2:'".$key3."'","post&c&".$key3."&".$row[0]),
        			            New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("公告4:'".$key4."'","post&d&".$key4."&".$row[0]),
                			);
                			$img_url = "https://thumbs.gfycat.com/WelloffSimilarAdmiralbutterfly-size_restricted.gif";
                			$button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("公佈欄", "要認真看", $img_url, $actions);
                			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
                			break;
        		case "confirm" :
        			$actions = array (
        				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("yes", "ans=y"),
        				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("no", "ans=N")
        			);
        			$button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder("problem", $actions);
        			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
        			break;
        		case "請假" :
                	$line_user_id = $event->getuserId();
                	$sql="select * from leader_db where `line_user_id`='$line_user_id'";
                	$rs = $db->query($sql);
                	$row = $rs->fetch();
                	
                	if($row['leader_id']==""){
                	    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("你不是小組長~滾Σ(・ω・ノ)ノ ┴─┴");
                	}
                	else{
                	    $db->exec("UPDATE `leader_db` SET `status`='4' WHERE `line_user_id`='$line_user_id'");
                	    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("輸入請假人的學號");
                	}
        			break;
        		case "算帳":
        		    $line_user_id = $event->getuserId();
        		    date_default_timezone_set('Asia/Taipei');
                    $date=  date("Y-m-d"); 
                    $check = $db->query("SELECT `leader_id` FROM `leader_db` WHERE `line_user_id`='".$line_user_id."'");
            		$check=$check->fetch();
        		    $sql="select student_db.leader_id,student_id,student_leave,checkin,checkout,late,attitude_score,score from student_db where leader_id='".$check['leader_id']."'";
        		    $query = $db->query($sql);
        		    if ($check==""){
        		        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("不是小組長~~滾Σ(・ω・ノ)ノ ┴─┴");
        		    }else{
        		        $sql="select leader_id from student_score where datetime='".$date."' and leader_id='".$check['leader_id']."'";
        		        $rs = $db->query($sql);
        		        $rs=  $rs->fetch();
        		        if($rs['leader_id']==""){
            		        $query = $query ->fetchAll();
                		    foreach ($query as $row){
                		        $score=$row['score'];
                		        if($row['student_leave']==NULL){
                    		        if($row['checkin']==0 && $row['checkout']==0 && $row['late']==0){
                    		            $score=$score-3;
                    		            $student_late="曠課";
                    		        }else if($row['checkin']==1 && $row['checkout']==0 && $row['late']==0){
                    		            $score=$score-1;
                    		            $student_late="早退";
                    		        }else if($row['checkin']==1 && $row['checkout']==0 && $row['late']==1){
                    		            $score=$score-1.5;
                    		            $student_late="遲到早退";
                    		        }else if($row['checkin']==1 && $row['checkout']==1 && $row['late']==1){
                    		            $score=$score-0.5;
                    		            $student_late="遲到";
                    		        }else if($row['checkin']==1 && $row['checkout']==1 && $row['late']==0){
                    		            $score=$score;
                    		            $student_late="到";
                    		        }
                    		        $db->exec("INSERT INTO `student_score` (leader_id,student_id,datetime,student_late,checkin,checkout,late,attitude_score) VALUE ('".$row['leader_id']."','".$row['student_id']."','".$date."','".$student_late."','".$row['checkin']."','".$row['checkout']."','".$row['late']."','".$row['attitude_score']."')");
                		        }else{
                		            if ($row['student_leave']=="公假" || $row['student_leave']=="喪假"){
                		                $score=$score;
                		            }else{
                		                $score=$score-3;
                		            }
                		            $db->exec("INSERT INTO `student_score` (leader_id,student_id,datetime,student_leave,checkin,checkout,late,attitude_score) VALUE ('".$row['leader_id']."','".$row['student_id']."','".$date."','".$row['student_leave']."','".$row['checkin']."','".$row['checkout']."','".$row['late']."','".$row['attitude_score']."')");
            		            }
            		        $db->exec("UPDATE `student_db` SET `score`='".$score."',`checkin`=0,`checkout`=0,`late`=0,`attitude_score`=0,`student_leave`=NULL,`datetime`=NULL WHERE `student_id`='".$row['student_id']."'");
            		        }
            		        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("啟動秘密任務<3");
        		        }else{
        		            $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("一天只能啟動一次喔>3");
        		        }
            		    
        		    }
        		    break;
        		case "出席分數":
        		      $line_user_id = $event->getuserId();
        		      $check = $db->query("SELECT `line_user_id` FROM `leader_db` WHERE `line_user_id`='".$line_user_id."'");
        		      $check=$check->fetch();
        		      if($check==""){
        		            $row=$db->query("SELECT `score` FROM `student_db` WHERE `line_student_id`='".$line_user_id."'");
        		            $row=$row->fetch();
        		            $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("目前分數:".$row['0']);
        		      }
        		      else{
            		      $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("輸入學號");
            		      $db->exec("UPDATE `leader_db` SET `status`='3' WHERE `line_user_id`='$line_user_id'");
        		        }
        		      break;
        		case "新增公告":
        		      $line_user_id = $event->getuserId();
        		      $check = $db->query("SELECT `line_user_id` FROM `leader_db` WHERE `line_user_id`='".$line_user_id."'");
        		      $check=$check->fetch();
        		      if($check==""){
        		          $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("不是小組長~~滾Σ(・ω・ノ)ノ ┴─┴");
        		      }
        		      else{
            		      $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("http://fs.mis.kuas.edu.tw/~s1104137115/linebot/add_announce.php");
        		        }
        		      break;
        		default :
        			$outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($messageText);
        			break;
        		}
    		}else if($a!=0){
    		    $messageText=strtolower(trim($event->getText()));
    		    
    		    $id_legal = $db->query("Select student_id From student_db Where student_id='".$messageText."'");
    		    $id_legal = $id_legal->fetch();
    		    
    		    if($id_legal['student_id']!="")
    		    {
        		    switch($a){
        		        case 2:
        		            $line_user_id = $event->getuserId();
                            $sqlleader="select * from leader_db where `line_user_id`='$line_user_id'";
        		            $rsleader = $db->query($sqlleader);
        		            $rsleader = $rsleader->fetch();
        		            $columns = array();
                			for($i=1;$i<5;$i++) {
                			    switch($i){
                			        case 1:
                			            $img_url = "https://i.ytimg.com/vi/1VNpCUMueMs/maxresdefault.jpg";
                			            $actions = array(
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("3月","lackoff&3&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("4月","lackoff&4&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("5月","lackoff&5&".$messageText)
                        				);
                        				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("春", " ", $img_url , $actions);
                        				$columns[] = $column;
                        				break;
                        			case 2:
                        			    $img_url = "https://i.imgur.com/h16DRhEr.jpg";
                        			    $actions = array(
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("6月","lackoff&6&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("7月","lackoff&7&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("8月","lackoff&8&".$messageText)
                        					
                        				);
                        				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("夏", " ", $img_url , $actions);
                        				$columns[] = $column;
                        				break;
                        			case 3:
                        			    $img_url = "https://s.yimg.com/bt/api/res/1.2/1KMFovS_GFBfU539Vg7QxA--/YXBwaWQ9eW5ld3NfbGVnbztxPTg1/http://cn.allthatstar.com/wp-content/uploads/2016/04/418.jpg";
                        			    $actions = array(
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("9月","lackoff&9&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("10月","lackoff&10&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("11月","lackoff&11&".$messageText)
                        				);
                        				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("秋", " ", $img_url , $actions);
                        				$columns[] = $column;
                        				break;                     
                        			case 4:
                        			     $img_url = "https://i.imgur.com/izGBl20.jpg";
                        			     $actions = array(
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("12月","lackoff&12&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("1月","lackoff&1&".$messageText),
                        					new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("2月","lackoff&2&".$messageText)
                        				);
                        				$column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("冬", " ", $img_url , $actions);
                        				$columns[] = $column;
                        				break;
                			    }
                			}
                			$db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='$line_user_id'");
                			$carousel = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columns);
                			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("Carousel Demo", $carousel);
                			break;
        		        case 3:
        		          $score=$db->query("SELECT `score` FROM `student_db` WHERE `student_id`='".$messageText."'");
        		          $score=$score->fetch();
        		          $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("學號:".$messageText."分數:".$score[0]);
        		          $db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='$line_user_id'");
        		          break;
        		        case 4:
        		            $actions = array (
                			    New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("事假","leave&a&".$messageText),
                				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("病假","leave&b&".$messageText),
                				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("公假","leave&c&".$messageText),
                				New \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("喪假","leave&d&".$messageText)
                			);
                			$img_url = "https://thumbs.gfycat.com/WelloffSimilarAdmiralbutterfly-size_restricted.gif";
                			$button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("請假", "真的要請", $img_url, $actions);
                			$outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
                			break;
                			
                			
                		
        		        default:
        		            $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("5678");
        		            break;
        		    }
    		    }
    		    else
    		    {
    		        $db->exec("UPDATE `leader_db` SET `status`='0' WHERE `line_user_id`='".$line_user_id."'");
    		        $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("輸入資料有誤喔<3");
    		    }
    		    
    		}
		    $response = $bot->replyMessage($event->getReplyToken(), $outputText);
	}
}
            $res->write('OK');
            return $res;
        });
    }
}
