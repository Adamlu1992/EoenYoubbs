<?php
define('IN_SAESPOT', 1);
define('CURRENT_DIR', pathinfo(__FILE__, PATHINFO_DIRNAME));

include(CURRENT_DIR . '/config.php');
include(CURRENT_DIR . '/common.php');

if (!$cur_user) exit('error: 401 login please');
if ($cur_user['flag']==0){
    header("content-Type: text/html; charset=UTF-8");
    exit('error: 403 该帐户已被禁用');
}else if($cur_user['flag']==1){
    header("content-Type: text/html; charset=UTF-8");
    exit('error: 401 该帐户还在审核中');
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$tid = isset($_GET['id']) ? intval($_GET['id']) : '0';
$page = isset($_GET['page']) ? intval($_GET['page']) : '1';
//
//// 处理操作
if($act && $tid > 0){
     //获取需要操作的消息数据
    $user_msg = $DBS->fetch_one_array("SELECT * FROM yunbbs_messages WHERE ID=$tid");
 //   if($act == 'setread'){
//         设置为已读，消息存在，并且消息是发给自己的
//        if($user_msg && $user_msg['ToUID'] == $cur_uid){
//            $DBS->unbuffered_query("UPDATE yunbbs_messages SET IsRead=1 WHERE ID='$tid'");
//            echo 1;
//        }else{
//            echo 0;
//        }
//        exit();
//    }else 
    if($act == 'del'){
        // 删除，只有发送消息的人才可以删除
        if($user_msg && $user_msg['FromUID'] == $cur_uid){
            $DBS->unbuffered_query("Delete from yunbbs_messages WHERE ID=$tid");
        }
    }
}

// 获取发送给我的未读私信的数量
$table_msgCount = $DBS->fetch_one_array("SELECT count(distinct ReferID) as count FROM `yunbbs_messages` where ToUID=$cur_uid or FromUID=$cur_uid");

$total_msg = $table_msgCount['count'];

$query_sql = "SELECT m.*,count(1) as count,u1.avatar FROM `yunbbs_messages` m
                inner join yunbbs_users u1 on m.FromUID=u1.id
                where fromuid=$cur_uid or touid=$cur_uid
                group by referid
                order by IsRead,id desc";

$query = $DBS->query($query_sql);
$messagedb=array();
while ($message = $DBS->fetch_array($query)) {
    // 格式化内容
    if($message['IsRead'] == '0' && $message['ToUID'] == $cur_uid){
         $message['Title'] = "未读";
     }else{
        $message['Title']='已读';
     }
    $message['AddTime'] = showtime($message['AddTime']);
    $messagedb[] = $message;
}
unset($message);
$DBS->free_result($query);

// 页面变量
$title = '私信';
$newest_nodes = get_newest_nodes();

$pagefile = CURRENT_DIR . '/templates/default/'.$tpl.'usermessage.php';

include(CURRENT_DIR . '/templates/default/'.$tpl.'layout.php');

?>
