<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class HistoryDaily extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:HistoryDaily';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Calcula els valors diaris i els posa a la DB';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$avui = (new DateTime())->format('Y-m-d');
		$ahir = date('Y-m-d',strtotime("-1 days"));
		$this->info('Busco dies');
		$dies = DB::table('consumreals')
		->select(DB::raw('distinct(DATE_FORMAT(date, "%Y-%m-%d")) as day'))
		->whereRaw(DB::raw('(DATE_FORMAT(date, "%Y-%m-%d") not in (select day from historydays)) AND (DATE_FORMAT(date, "%Y-%c-%d")!="'.$avui.'" )'))
		->groupby('day') 
		->get();
              //      $queries = DB::getQueryLog();
               //     print_r($queries);
             //       print_r($ahir); die();
		foreach ($dies as $daily)    {
			list($any, $mes, $dia) = explode('-', $daily->day);
			$a=ConsumrealsController::consumdia($dia,$mes,$any); 
	      //print_r($daily->day);
			$obj= new Historyday;
			$obj->day=$daily->day;
			$obj->consum=$a['consum'];
			$obj->pic=$a['pic'];
			$obj->save();
			$this->info('Dia:'.$daily->day.' Consum:'.$a['consum'].' Pic:'.$a['pic']);
	      //print_r($obj);
	      //Usage
			File::put('public/history/'.$daily->day.'.json',ConsumrealsController::consumdiajson($dia,$mes,$any));
		}

		DB::table('consumreals')
		->whereRaw(DB::raw('date not like "'.$ahir.'%" and date not like "'.$avui.'%"'))
		->delete();
		//$a=ConsumrealsController::consumdia($dia,$mes,$any)

		/*  select distinct(DATE_FORMAT(day, "%Y-%m-01")) as day2
                    from historydays 
                    where DATE_FORMAT(day, "%Y-%m-%d") not in (select month from historymonths) and (DATE_FORMAT(NOW(), "%Y-%m") not like DATE_FORMAT(day, "%Y-%m")))
        */

		$this->info('Busco mesos');
		$mesos = DB::table('historydays')
		->select(DB::raw('distinct(DATE_FORMAT(day, "%Y-%m-01")) as month, SUM(consum) as consum, MAX(pic) as pic'))
		->whereRaw(DB::raw('DATE_FORMAT(day, "%Y-%m-01") not in (select month from historymonths) and (DATE_FORMAT(NOW(), "%Y-%m") not like DATE_FORMAT(day, "%Y-%m"))'))
		->get();
		foreach ($mesos as $mes)    {
			$this->info('Mes: '.$mes->month.' Consum: '.$mes->consum.' Pic: '.$mes->pic);
			$obj= new Historymonth;
			$obj->month=$mes->month;
			$obj->consum=$mes->consum;
			$obj->pic=$mes->pic;
			$obj->save();
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			);
	}

}
