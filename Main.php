<?php

include __DIR__.'\vendor\autoload.php';

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\ActionRow;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;

use Thedudeguy\Rcon;

use React\EventLoop\Loop;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

const TOKEN = 'BotTokenHere';// get token from here(https://discord.com/developers/)
const GUILD_ID = '00000000000000000';
const CHANNEL_ID = '000000000000000000';
const START_WIN = "C:\~\start.cmd";
const START_LINUX = "/home/~/start.sh";
const SERVER_IP = "localhost";
const SERVER_PORT = '19132';
const RCON_PASS = "Pass"; //Please use the RconServer(https://github.com/pmmp/RconServer) if you are using pmmp. 

$loop = Loop::get();

$logger = new Logger('Logger');
// $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));

$discord = new Discord([
    'token' => TOKEN,
    'logger' => $logger,
    'loop' => $loop
]);

// $time = $loop->addPeriodicTimer(1, function() use ($discord){
//     $this->task($discord);
// });

$cooltime = null;

$discord->on('ready', function ($discord){

    echo "Bot is ready.", PHP_EOL;

    global $cooltime;

    $guild = $discord->guilds->get('id', GUILD_ID);
    $channel = $guild->channels->get('id', CHANNEL_ID);
            
    // $channel->getMessageHistory([])->done(function ($messages) use ($channel) {
    //     $message->delete();
    // });

    $channel->getMessageHistory([])->then(function ($messages) use ($channel) {
        $channel->deleteMessages($messages);
    });

    $start = Button::new(Button::STYLE_SUCCESS)
    ->setLabel('サーバーを起動')
    ->setCustomId('StartServer')
    ->setListener(function(Interaction $interaction){

        global $cooltime;

        if($cooltime !== null){
            if(($cooltime + 10) > time()){
                $at = 10 - (time() - $cooltime);
                $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("クールタイム中です\n残り: {$at} 秒"), true);
                return;
            }
        }

        
        if((substr(PHP_OS, 0, 3) !== 'WIN')){
            /// Linux系OSの場合
            exec(START_LINUX . ' >/dev/null 2>&1 &');
        }else{
            /// Windows系OSの場合
            $fp = popen('start "" '.START_WIN, 'r');
            pclose($fp);
        }
        print_r("Start\n");
        $interaction->respondWithMessage(MessageBuilder::new()
        ->setContent('サーバーを起動しています...'), true);
        $cooltime = time();

    }, $discord);

    $end = Button::new(Button::STYLE_SUCCESS)
    ->setLabel('サーバーを停止')
    ->setCustomId('StopServer')
    ->setListener(function(Interaction $interaction){

        global $cooltime;

        if($cooltime !== null){
            if(($cooltime + 10) > time()){
                $at = 10 - (time() - $cooltime);
                $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("クールタイム中です\n残り: {$at} 秒"), true);
                return;
            }
        }

        // $interaction->respondWithMessage(MessageBuilder::new()
        // ->setContent('サーバーを停止させています...'), true);
        $interaction->sendFollowUpMessage(MessageBuilder::new()
        ->setContent('サーバーを停止させています...'), true);
        print_r("End\n");

        $rcon = new Rcon(SERVER_IP, SERVER_PORT, RCON_PASS, 3);
        if($rcon->connect()){
            // $interaction->respondWithMessage(MessageBuilder::new()
            // ->setContent('実行しました'), true);
            $interaction->sendFollowUpMessage(MessageBuilder::new()
            ->setContent('実行しました'), true);
            $rcon->sendCommand('stop');
            print_r('success');
        } else {
            print_r("Faild\n");
            $interaction->respondWithMessage(MessageBuilder::new()
            ->setContent('Rconでの接続に失敗しました'), true);
        }

        $cooltime = time();

    }, $discord);

    $row = ActionRow::new()
    ->addComponent($start)
    ->addComponent($end);

    $message = MessageBuilder::new()
    ->setContent('ボタンで操作してください')
    ->addComponent($row);

    $channel->sendMessage($message);

    $discord->on(EVENT::MESSAGE_CREATE, function ($message) use($discord){

        if($message->user_id == $discord->user->id) return;
        if($message->channel_id != CHANNEL_ID) return;
        
        $message->delete();

    });

});

$discord->run();

?>