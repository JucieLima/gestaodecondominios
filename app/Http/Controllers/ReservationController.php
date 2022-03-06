<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaDisabledDay;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    /**
     * @param int $id
     * @param Request $request
     * @return string[]
     */
    public function setReservation(int $id, Request $request): array
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(),[
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i:s',
            'unit' => 'required'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $unit = Unit::find($request->input('unit'));
        $area = Area::find($id);

        if(!$unit){
            $array['error'] = "A unidade que você informou não está cadastrada no sistema!";
            return $array;
        }elseif (!$area){
            $array['error'] = "A área que você informou não está cadastrada no sistema!";
            return $array;
        }

        $can = true;

        //Verifica se está dentro da disponibilidade de dia e hora
        $weekDay = date('w', strtotime($request->input('date')));
        $allowedDays = explode(',', $area['days']);
        $start = strtotime($area['starts_at']);
        $end = strtotime("-1 hour", strtotime($area['ends_at']));
        $reservation_time= strtotime($request->input('time'));

        if(!in_array($weekDay, $allowedDays)){
            $can = false;
            $array['day'] = false;
        }else if($reservation_time < $start || $reservation_time > $end){
            $array['hour'] = false;
            $can = false;
        }

        //Verifica se a data e hora já passaram
        if($reservation_time < strtotime(date('Y-m-d H:00:00'))){
            $array['day'] = false;
            $array['hour'] = false;
            $can = false;
        }

        //Verifica se está fora dos disabled days
        $existingDisabledDays = AreaDisabledDay::where("area", $id)->where("day", $request->input('date'))->count();
        if($existingDisabledDays > 0){
            $array['out'] = true;
            $can = false;
        }

        //Verifica se não existe outra reserva no mesmo dia/hora.
        $existingReservation = Reservation::where("area", $id)
            ->where("day", $request->input('date'). ' '.$request->input('time'))
            ->count();

        if($existingReservation > 0){
            $array['busy'] = true;
            $can = false;
        }

        if(!$can){
            $array['error'] = "Reserva não permitida neste dia e horário!";
            return $array;
        }

        $reservation = new Reservation();
        $reservation->unit = $request->input('unit');
        $reservation->area = $id;
        $reservation->day = $request->input('date') . ' ' . $request->input('time');
        $reservation->save();

        $array['result'] = "Reserva realizada com sucesso!";

        return $array;
    }

    /**
     * @param int $id
     * @return array|string[]
     */
    public function getDisabledDates(int $id): array
    {
        $array = ['error' => ''];

        $area = Area::find($id);
        if(!$area){
            $array['error'] = "Área inexistente.";
            return $array;
        }

        $disabledDays = AreaDisabledDay::where("id", $id)->get();
        foreach ($disabledDays as $day){
            $array['list'][] = $day;
        }
        $offDays = [];
        $allowedDays = explode(',', $area['days']);
        for($q = 0; $q < 7; $q++){
            if(!in_array($q, $allowedDays)){
                $offDays[] = $q;
            }
        }

        $start = time();
        $end = strtotime('+3 months');
        for($current = $start; $current < $end; $current = strtotime('+1 day', $current)){
            $wd = date('w', $current);
            if(in_array($wd, $offDays)){
                $array['list'][] = date('Y-m-d', $current);
            }
        }

        return $array;
    }

    /**
     * @param int $id
     * @param Request $request
     * @return array|string[]
     */
    public function getTimes(int $id, Request $request): array
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(),[
            'date' => 'required|date_format:Y-m-d'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        $date = $request->input('date');
        $area = Area::find($id);

        if(!$area){
            $array['error'] = 'Área inexistente.';
            return $array;
        }

        $can = true;

        //Verificar se o dia está desabilitado
        $existingDisabledDay = AreaDisabledDay::where("id", $id)
            ->where("day", $date)
            ->count();

        if($existingDisabledDay > 0){
            $can = false;
        }
        //Verificar se o dia está habilitado
        $allowedDays = explode(',', $area['days']);
        $weekday = date('w', strtotime($date));
        if(!in_array($weekday, $allowedDays)){
            $can = false;
        }

        if($can){

            $start = strtotime($area['starts_at']);
            $end = strtotime(($area['ends_at']));

            $times = [];

            for($lastTime = $start; $lastTime < $end; $lastTime = strtotime('+1 hour', $lastTime)){
                $times[] = $lastTime;
            }

            $timeList = [];
            foreach ($times as $time){
                $timeList[] = [
                  'id' => date('H:i:s', $time),
                  'title' =>   date('H:i', $time).' - '. date('H:i', strtotime('+1 hour', $time)),
                  'available' => true
                ];
            }

            //Remover reservas
            $reservations = Reservation::where('area', $id)
            ->whereBetween('day', [
                $date.' 00:00:00',
                $date.' 23:59:59'
            ])
            ->get();

            $toRemove = [];
            foreach ($reservations as $reservation){
                $toRemove[] = date('H:i:s', strtotime($reservation['day']));
            }
            foreach ($timeList as $item){
                if(in_array($item['id'], $toRemove)){

                    $item['available'] = false;
                }
                $array['list'][] = $item;
            }

            //Remover horários que já passaram
            if($date == date('Y-m-d')){
                for($i = 0; $i < count($array['list']); $i++){
                    if($array['list'][$i]['id'] < date('H:00:00')){
                        $array['list'][$i]['available'] = false;
                    }
                }
            }

            //Remover dias que já passaram
            if($date < date('Y-m-d')){
                for($i = 0; $i < count($array['list']); $i++){
                    $array['list'][$i]['available'] = false;
                }
            }

        }
        return $array;
    }

    /**
     * @param Request $request
     * @return array|string[]
     */
    public function getMyReservations(Request $request): array
    {
        $array = ['error' => ''];
        $unit = $request->input(['unit']);
        if(!$unit){
            $array = ['error' => 'Informe a unidade!'];
            return $array;
        }

        $find = Unit::find($unit);
        if(!$find){
            $array = ['error' => 'Unidade não localizada no sistema!'];
            return $array;
        }

        $reservations = Reservation::where('unit', $unit)
            ->where('day', '>=', date('Y-m-d H:00:00'))
            ->orderBy('day', 'ASC')
            ->get();

        foreach ($reservations as $reservation){
           $area = Area::find($reservation['area']);
           $start = date('d/m/Y H:i:s', strtotime($reservation['day']));
           $dateEnd = date('d/m/Y H:i:s', strtotime('+1 hour', strtotime($reservation['day'])));
           $date = $start. ' à ' . $dateEnd;
           $array['list'][] = [
               'id' => $reservation['id'],
               'area' => $reservation['area'],
               'title' => $area['title'],
               'cover' => asset('storage/'.$area['cover']),
               'reservation' => $date,
               'start' => $start
           ];
        }

        return $array;
    }

    /**
     * @param int $id
     * @return string[]
     */
    public function deleteMyReservation(int $id): array
    {
        $array = ['error' => ''];

        $user = auth()->user();
        $reservation = Reservation::find($id);
        if(!$reservation){
            $array['error'] = "Reserva inexistente!";
            return $array;
        }

        $unit = Unit::where('id', $reservation['unit'])->where('owner', $user['id'])->count();

        if($unit < 1){
            $array['error'] = "Não é possível excluir reservas realizadas por outros usuários.";
            return $array;
        }

        $reservation->delete();
        $array['result'] = "Reserva excluída com sucesso!";

        return $array;
    }
}
