<?php
function toetapPaceText($secPerKm){
    if(!$secPerKm) return '—';
    $m=(int)floor($secPerKm/60); $s=(int)round($secPerKm-$m*60);
    if($s===60){$m++;$s=0;}
    return $m.':'.str_pad((string)$s,2,'0',STR_PAD_LEFT).'/km';
}
function toetapInferRoles(array $runs): array {
    if(!$runs) return ['roles'=>['UNCLASSIFIED'],'mix'=>[],'reason'=>'Not enough running data yet.'];
    $n=count($runs); $long=0;$shortFast=0;$raceSignal=0;$easy=0;
    $paces=[];
    foreach($runs as $r){
        $km=(float)($r['km']??0); $pace=(float)($r['pace']??0);
        if($pace>0)$paces[]=$pace;
        if($km>=14)$long++;
        if($km>=4.5 && $km<=12.5)$shortFast++;
        if((int)($r['pr_count']??0)>0)$raceSignal++;
    }
    sort($paces); $median=$paces ? $paces[(int)floor((count($paces)-1)/2)] : 0;
    foreach($runs as $r){ if($median && ($r['pace']??0) >= $median*1.06) $easy++; }

    $scores=[
      'DAILY'=>max(0,$n*1.0 + $easy*.7),
      'LONG RUN'=>$long*4.0 + max(0,$n-2)*.15,
      'SPEED'=>$shortFast*1.2 + $raceSignal*1.5,
      'RACE'=>$raceSignal*5.0
    ];
    // Race is intentionally conservative; no PR/race signal means it is not inferred just from speed.
    if($raceSignal===0)$scores['RACE']=0;
    arsort($scores);
    $roles=[];
    foreach($scores as $k=>$v){
        if($v<=0)continue;
        if(!$roles || $v >= reset($scores)*0.55)$roles[]=$k;
        if(count($roles)>=2)break;
    }
    if(!$roles)$roles=['DAILY'];
    $total=array_sum($scores);$mix=[];
    if($total>0)foreach($scores as $k=>$v)if($v>0)$mix[$k]=(int)round($v/$total*100);
    $reason='Inferred from your distance, pace, usage frequency and Strava PR signals.';
    return ['roles'=>$roles,'mix'=>$mix,'reason'=>$reason];
}
function toetapRoleLabel(array $profile): string {
    return implode(' / ',$profile['roles']??['UNCLASSIFIED']);
}
?>