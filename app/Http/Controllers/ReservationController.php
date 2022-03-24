<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaDisabledDay;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function setReservation(int $id, Request $request): JsonResponse
    {
        $array = [];
        $validator = Validator::make($request->all(),[
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i:s',
            'unit' => 'required'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 400);
        }

        $unit = Unit::find($request->input('unit'));
        $area = Area::find($id);

        if(!$unit){
            $array['error'] = "A unidade que você informou não está cadastrada no sistema!";
            return response()->json($array, 404);
        }elseif (!$area){
            $array['error'] = "A área que você informou não está cadastrada no sistema!";
            return response()->json($array, 404);
        }

        $can = true;

        //Verifica se está dentro da disponibilidade de dia e hora
        $weekDay = date('w', strtotime($request->input('date')));
        $allowedDays = explode(',', $area['days']);
        $start = strtotime($area['starts_at']);
        $end = strtotime("-1 hour", strtotime($area['ends_at']));
        $reservation_time = strtotime($request->input('date').' '.$request->input('time'));

        if(!in_array($weekDay, $allowedDays)){
            $can = false;
            $array['day'] = false;
        }else if($area['mode'] == 'hour' && ($reservation_time < $start || $reservation_time > $end)){
            $array['hour'] = false;
            $can = false;
        }

        //Verifica se a data e hora já passaram
        if($reservation_time < strtotime(date('Y-m-d H:00:00'))){
            $array['date'] = date('Y-m-d H:i:s', strtotime($reservation_time));
            $array['day'] = false;
            $array['hour'] = false;
            $can = false;
        }

        //Verifica se está fora dos disabled days
        $existingDisabledDays = AreaDisabledDay::where("area_id", $id)->where("date", $request->input('date'))->count();
        if($existingDisabledDays > 0){
            $array['out'] = true;
            $can = false;
        }

        //Verifica se não existe outra reserva no mesmo dia/hora.
        $existingReservation = Reservation::where("area_id", $id)
            ->where("date", $request->input('date'). ' '.$request->input('time'))
            ->count();

        if($existingReservation > 0){
            $array['busy'] = true;
            $can = false;
        }

        if(!$can){
            $array['error'] = "Reserva não permitida neste dia e horário!";
            return response()->json($array, 400);
        }

        $reservation = new Reservation();
        $reservation->unit_id = $request->input('unit');
        $reservation->area_id = $id;
        $reservation->date = $request->input('date') . ' ' . $request->input('time');
        $reservation->save();

        $array['result'] = "Reserva realizada com sucesso!";

        return response()->json($array);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getTimes(int $id, Request $request): JsonResponse
    {
        $array = [];

        $validator = Validator::make($request->all(),[
            'date' => 'required|date_format:Y-m-d'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->first();
            return response()->json($array, 401);
        }

        $date = $request->input('date');
        $area = Area::find($id);

        if(!$area){
            $array['error'] = 'Área inexistente.';
            return response()->json($array, 404);
        }

        $can = true;

        //Verificar se o dia está desabilitado
        $existingDisabledDay = AreaDisabledDay::where("area_id", $id)
            ->where("date", $date)
            ->count();

        if($existingDisabledDay > 0){
            $can = false;
        }
        //Verificar se o dia está habilitado
        $allowedDays = explode(',', $area['days']);
        $weekday = date('w', strtotime($date));

        $myWeek = [6, 0, 1, 2, 3, 4, 5];

        if(!in_array($myWeek[$weekday], $allowedDays)){
            $can = false;
        }

        $start = strtotime($area['starts_at']);
        $end = strtotime(($area['ends_at']));

        $times = [];
        $timeList = [];

        for($lastTime = $start; $lastTime < $end; $lastTime = strtotime('+1 hour', $lastTime)){
            $times[] = $lastTime;
        }

        if($can){
            foreach ($times as $time){
                $timeList[] = [
                  'id' => date('H:i:s', $time),
                  'title' =>   date('H:i', $time).' - '. date('H:i', strtotime('+1 hour', $time)),
                  'available' => true
                ];
            }

            //Remover reservas
            $reservations = Reservation::where('area_id', $id)
            ->whereBetween('date', [
                $date.' 00:00:00',
                $date.' 23:59:59'
            ])
            ->get();

            $toRemove = [];
            foreach ($reservations as $reservation){
                $toRemove[] = date('H:i:s', strtotime($reservation['date']));
            }
            foreach ($timeList as $item){
                if(in_array($item['id'], $toRemove)){

                    $item['available'] = false;
                }
                $array['response'][] = $item;
            }

            //Remover horários que já passaram
            if($date == date('Y-m-d')){
                for($i = 0; $i < count($array['response']); $i++){
                    if($array['response'][$i]['id'] < date('H:00:00')){
                        $array['response'][$i]['available'] = false;
                    }
                }
            }

            //Remover dias que já passaram
            if($date < date('Y-m-d')){
                for($i = 0; $i < count($array['response']); $i++){
                    $array['response'][$i]['available'] = false;
                }
            }

        }else{
            foreach ($times as $time){
                $timeList[] = [
                    'id' => date('H:i:s', $time),
                    'title' =>   date('H:i', $time).' - '. date('H:i', strtotime('+1 hour', $time)),
                    'available' => false
                ];
            }

            $array['response'] = $timeList;
        }
        return response()->json($array);
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
            ->where('date', '>=', date('Y-m-d H:00:00'))
            ->orderBy('date', 'ASC')
            ->get();

        foreach ($reservations as $reservation){
           $area = Area::find($reservation['area_id']);
           $start = date('d/m/Y H:i:s', strtotime($reservation['date']));
           $dateEnd = date('d/m/Y H:i:s', strtotime('+1 hour', strtotime($reservation['date'])));
           $date = $start. ' à ' . $dateEnd;
           $array['response'][] = [
               'id' => $reservation['id'],
               'area' => $reservation['area_id'],
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

        $unit = Unit::where('id', $reservation['unit_id'])->where('owner', $user['id'])->count();

        if($unit < 1){
            $array['error'] = "Não é possível excluir reservas realizadas por outros usuários.";
            return $array;
        }

        $reservation->delete();
        $array['result'] = "Reserva excluída com sucesso!";

        return $array;
    }
}
