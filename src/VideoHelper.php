<?php
/**
 * 常见视频操作类
 * 可用于在视频什么时间、什么地方、打多长多大的水印
 * User: Ron(chenhongron@163.com)
 * Date: 16-10-13
 * Time: 上午11:55
 */

namespace Upyun\VideoHelp;

use Upyun\Sugar\AvPretreatment;
use Upyun\Sugar\Tasks;
use Upyun\Sugar\CallbackValidation;

class VideoHelper extends BaseHelper 
{
    const DEFAULT_NOTIFY_URL = "http://paopaoxia-upload.b0.aicdn.com";
    
    /**
     * 使用upyun api生成截图
     * @param string $path       空间视频地址 
     * @param string $notifyUrl  回调通知地址
     * @return void|boolean|string
     */
    public static function createthumbnailByUpyunApi($path, $notifyUrl=self::DEFAULT_NOTIFY_URL)
    {
        if( empty($path) )
            return ;

        $bucketName = \Yii::$app->params['bucketName'];
        $upyunUrl = \Yii::$app->params['upyun_url'];
        
        $thumbFormat = 'png';
        $saveAs = "thumbnailVideo/".date('Y-m-d')."/".self::setFileName($thumbFormat);
        
        $data = array(
            'bucket_name' => $bucketName,        //空间名
            'source' => $path,                  //空间视频地址
            'notify_url' => $notifyUrl,         //回调通知地址
            'tasks' => array(                           //针对一个视频，可以有多种处理任务
                array(
                    'type' => 'thumbnail',
                    'thumb_single' => true,
                    'thumb_amount' => 1,
                    'thumb_format' => $thumbFormat,
                    'save_as' => $saveAs
                ),
            )
        );
        
        $status = self::requestApi($data);
        if( !$status )
            return false;
        
        return $upyunUrl.$saveAs;
    }
    
    /**
     * 使用upyun api剪切视频
     * @param string $path       空间视频地址
     * @param array  $data       多种处理任务(看getDataCutTwo()与getDataCutThree())
     * @param string $notifyUrl  回调通知地址
     * @return void|boolean|string
     */
    public static function cutVideoByUpyunApi($path, $data, $notifyUrl=self::DEFAULT_NOTIFY_URL)
    {
        $bucketName = \Yii::$app->params['bucketName'];
        $upyunUrl = \Yii::$app->params['upyun_url'];
        
        $requestData = array(
            'bucket_name' => $bucketName,        
            'source' => $path,                  
            'notify_url' => $notifyUrl,         
            'tasks' => $data
        );
        
        $status = self::requestApi($requestData);
        if( !$status )
            return false;
        
        return $status;
    }
    
    /**
     * 使用upyun api给视频打水印
     * @param string $data       数据
     * @param string $path       空间视频地址
     * @param string $watermarkPath 保存的路径
     * @param string $notifyUrl  回调通知地址
     * @return void|boolean|string
     */
    public static function watermarkVideoByUpyunApi($data, $path, $watermarkPath, $notifyUrl=self::DEFAULT_NOTIFY_URL)
    {
        $bucketName = \Yii::$app->params['bucketName'];
        $upyunUrl = \Yii::$app->params['upyun_url'];
        
        $requestData = array(
            'bucket_name' => $bucketName,
            'source' => $path,
            'notify_url' => $notifyUrl,
            'tasks' => array(                           //针对一个视频，可以有多种处理任务
                array(
        			'type' => 'video',
        			'watermark_img' => $data['pictrue'],
        			'watermark_gravity' => 'northwest',
        			'watermark_dx' => (int)$data['watermark_dx'],
        			'watermark_dy' => (int)$data['watermark_dy'],
        			'save_as'=>$watermarkPath,          // 结果存放路径，如：/path/to/fw_100.jp
                )
    		)
        );
        
        $status = self::requestApi($requestData);
        if( !$status )
            return false;
        
        return $status;
    }    
    
    /**
     * 使用upyun api合并视频
     * @param string $avopts     合并串
     * @param string $path       空间视频地址
     * @param string $mergePath  保存的路径
     * @param string $notifyUrl  回调通知地址
     * @return void|boolean|string
     */
    public static function mergeVideoByUpyunApi($avopts, $mergePath, $path, $notifyUrl=self::DEFAULT_NOTIFY_URL)
    {
        $bucketName = \Yii::$app->params['bucketName'];
        $upyunUrl = \Yii::$app->params['upyun_url'];
    
        $requestData = array(
            'bucket_name' => $bucketName,
            'source' => $path,
            'notify_url' => $notifyUrl,
            'tasks' => array(                           //针对一个视频，可以有多种处理任务
                array(
                    'type' => 'vconcat',
                    'avopts' => $avopts,
        			'save_as' => $mergePath
                ),
            )
        );
    
        $status = self::requestApi($requestData);
        if( !$status )
            return false;
    
        return $status;
    }
    
    private static function requestApi($data)
    {
        if( empty($data) || !is_array($data) )
            return false;
        
        $operatorName = \Yii::$app->params['operatorName'];
        $operatorPassword = \Yii::$app->params['operatorPassword'];
        
        $sugar = new AvPretreatment($operatorName, $operatorPassword);//操作员的帐号密码
        
        try {
            //返回对应的任务ids
            $result = $sugar->request($data);
        } catch(\Exception $e) {
            $result = false;
        }
        
        return $result;
    }
    
    public static function getDataCutTwo($start_time, $boundary_time, $clip_upper, $clip_lower)
    {
        return [
                [
                    'type' => 'video',
                    'start_time' => $start_time,
                    'end_time' => $boundary_time,
                    'save_as'=>$clip_upper,
                ],
                [
                    'type' => 'video',
                    'start_time' => $boundary_time,
                    'save_as'=>$clip_lower,
                ],
            ];
    }
    
    public static function getDataCutThree($start_time, $boundary_time, $clip_upper, $three_time, $clip_middle, $clip_lower)
    {
        return [
            [
                'type' => 'video',
                'start_time' => $start_time,
                'end_time' => $boundary_time,
                'save_as'=>$clip_upper,
            ],
            [
                'type' => 'video',
                'start_time' => $boundary_time,
                'end_time' => $three_time,
                'save_as'=>$clip_middle,
            ],
            [
                'type' => 'video',
                'start_time' => $three_time,
                'save_as'=>$clip_lower,
            ],
        ];
    }
    
    private static function setFileName($suffix="jpg")
    {
        return time().mt_rand(10000,99999).".".$suffix;
    }
    
    //任务编号
    public static function createTaskNo()
    {
        return "vm".self::createFileName();
    }
    
    public static function createFileName()
    {
        return time().mt_rand(10000,99999);
    }
    
    //获取视频时长
    public static function getVideoTime($file)
    {
        if( empty($file) )
            return "";
    
        return exec("ffmpeg -i ".$file." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");
    }
    
    //CURL文件抓取
    public static function getFileFromHttp($url) 
    {
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        ob_start ();
        curl_exec ( $ch );
        $return_content = ob_get_contents ();
        ob_end_clean ();
    
        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
        
        return $return_content;
    }
} 