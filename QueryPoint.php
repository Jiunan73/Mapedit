<?php
include('DB_Function.php');
include('Data_Function.php');
include('DB_Login.php');
session_start();	
// 接收 fetch 傳遞的目錄路徑參數
		$db='agvslam';
		
		$postData = file_get_contents("php://input");
		$data = json_decode($postData);
		$db='agvslam';
		//$data=$_SESSION['data'];
		$floor_no=$data->floor_no;  
		//var_dump($_SESSION['data']);
		//$_SESSION['data']=$data;
		//$floor_no=5;
		$Query="SELECT floor_no,Tag_ID,Tag_name,X,Y,Z,slam,floor,MFG_tag,shelf_car_type,Retreat_Flag from point where floor_no=".$floor_no;
		//echo $Query;
		
		$Result=MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "SELECT", $Query);
		
		


			$points=array();
			for ($i = 1; $i <= count($Result)-1; $i++) {
				$point['floorid']=$Result[$i][0];
				$point['tagID']=$Result[$i][1];
				$point['tagName']=$Result[$i][2];
				$point['x']=$Result[$i][3];
				$point['y']=$Result[$i][4];
				$point['z']=$Result[$i][5];
				$point['PositionNotice']=$Result[$i][6];		
				$point['floor']=$Result[$i][7];		
				$point['MFG_tag']=$Result[$i][8];
				$point['shelf_car_type']=$Result[$i][9];	
				$point['Retreat_Flag']=$Result[$i][10];				
				$points[]=$point;
			}
		$Query="SELECT `From_Point` , b.X AS FromX, b.y AS FromY, `To_Point` , c.X AS ToX, c.y AS ToY, `Forward_Sensor` , `Forward_Speed` , `backward_Sensor` , `backward_Speed` , `path_type` , `FORK_BACK`
				FROM path a
				LEFT JOIN POINT b ON a.From_Point = b.Tag_ID
				LEFT JOIN POINT c ON a.To_Point = c.Tag_ID
				WHERE 1
				AND a.active = '1'
		and b.floor_no = ".$floor_no;
		$Result=MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "SELECT", $Query);
		$Paths=array();
			for ($i = 1; $i <= count($Result)-1; $i++) {
				for($j=0;$j<count($Result[0]);$j++)
				{
					$Path[$Result[0][$j]]=$Result[$i][$j];
				}
			
				$Paths[]=$Path;
			}
// 輸出回傳結果
/*select b.Tag_name 'FromLoc', b.X 'FromX', b.Y 'FromY', a.To_Point ,a.Forward_Sensor,a.Forward_Speed,a.backward_Sensor,a.backward_Speed
	from path a, point b 
	where a.From_Point = b.Tag_ID 
	and a.active = '1'
	and b.floor like '" . $floor . "'*/
		$data1['Points']=$points;
		$data1['Paths']=$Paths;
		echo json_encode($data1);

?>
