<?php
class Psgc
{
    private static $snapshot;
    public static function data(): array {
        if (self::$snapshot===null) {
            $raw=@file_get_contents(__DIR__.'/../data/psgc.json');
            $data=$raw?json_decode($raw,true):null;
            if (!is_array($data)||empty($data['barangays'])) throw new DomainException('Address choices are unavailable. Please try again later.');
            self::$snapshot=$data;
        }
        return self::$snapshot;
    }
    public static function options(string $level,string $parent='',string $province=''): array {
        $d=self::data();$rows=[];
        if ($level==='regions') $rows=$d['regions'];
        elseif ($level==='provinces') {
            $rows=array_values(array_filter($d['provinces'],static function($r)use($parent){return $r['region']===$parent;}));
            foreach ($d['cities'] as $c) if ($c['region']===$parent && $c['province']==='') { $rows[]=['code'=>'none','name'=>'No province / independent city','region'=>$parent];break; }
        } elseif ($level==='cities') $rows=array_values(array_filter($d['cities'],static function($r)use($parent,$province){return $r['region']===$parent && $r['province']===($province==='none'?'':$province);}));
        elseif ($level==='barangays') $rows=array_values(array_filter($d['barangays'],static function($r)use($parent){return $r['city']===$parent;}));
        usort($rows,static function($a,$b){return strcasecmp($a['name'],$b['name']);});return $rows;
    }
    public static function validate(array $input): array {
        $names=[];$errors=[];$region='';$province='';$city='';
        foreach (['region','province','city','barangay'] as $level) {
            $key='psgc_'.$level;$code=is_string($input[$key]??null)?$input[$key]:'';
            $options=$level==='region'?self::options('regions'):($level==='province'?self::options('provinces',$region):($level==='city'?self::options('cities',$region,$province):self::options('barangays',$city)));
            $found=null;foreach($options as $option) if ($option['code']===$code) {$found=$option;break;}
            if (!$found) { $errors[$key]='Select a valid '.($level==='city'?'city / municipality':$level).'.';break; }
            $names[$key]=$code;$names[$level]=$level==='province'&&$code==='none'?'':$found['name'];
            if ($level==='region')$region=$code;elseif($level==='province')$province=$code;elseif($level==='city')$city=$code;
        }
        return [$names,$errors];
    }
    public static function formatted(array $details): string {
        return implode(', ',array_filter([$details['address']??'',$details['barangay']??'',$details['city']??'',$details['province']??'',$details['region']??'',$details['postal_code']??$details['zip']??'']));
    }
}
