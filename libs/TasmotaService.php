<?php

class TasmotaService extends IPSModule
{
    protected function MQTTCommand($command, $msg)
    {
        $FullTopic = explode('/', $this->ReadPropertyString('FullTopic'));
        $PrefixIndex = array_search('%prefix%', $FullTopic);
        $TopicIndex = array_search('%topic%', $FullTopic);

        $SetCommandArr = $FullTopic;
        $index = count($SetCommandArr);

        $SetCommandArr[$PrefixIndex] = 'cmnd';
        $SetCommandArr[$TopicIndex] = $this->ReadPropertyString('Topic');
        $SetCommandArr[$index] = $command;

        $topic = implode('/', $SetCommandArr);
        $msg = $msg;

        $Buffer['Topic'] = $topic;
        $Buffer['MSG'] = $msg;
        $BufferJSON = json_encode($Buffer);

        return $BufferJSON;
    }

    protected function Debug($Meldungsname, $Daten, $Category)
    {
        if ($this->ReadPropertyBoolean($Category) == true) {
            $this->SendDebug($Meldungsname, $Daten, 0);
        }
    }

    protected function setPowerOnStateInForm($value)
    {
        if ($value != $this->ReadPropertyInteger('PowerOnState')) {
            IPS_SetProperty($this->InstanceID, 'PowerOnState', $value);
            if (IPS_HasChanges($this->InstanceID)) {
                IPS_ApplyChanges($this->InstanceID);
            }
        }
        return true;
    }

    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 1) {
                throw new Exception('Variable profile type does not match for profile ' . $Name);
            }
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[count($Associations) - 1][0];
        }

        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 0) {
                throw new Exception('Variable profile type does not match for profile ' . $Name);
            }
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }

    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[count($Associations) - 1][0];
        }

        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

    public function restart()
    {
        $command = 'restart';
        $msg = 1;
        $BufferJSON = $this->MQTTCommand($command, $msg);
        $this->SendDebug('restart', $BufferJSON, 0);
        $this->SendDataToParent(json_encode(array('DataID' => '{018EF6B5-AB94-40C6-AA53-46943E824ACF}', 'Action' => 'Publish', 'Buffer' => $BufferJSON)));
    }

    public function sendMQTTCommand(string $command, string $msg)
    {
        $BufferJSON = $this->MQTTCommand($command, $msg);
        $this->SendDebug('sendMQTTCommand', $BufferJSON, 0);
        $this->SendDataToParent(json_encode(array('DataID' => '{018EF6B5-AB94-40C6-AA53-46943E824ACF}', 'Action' => 'Publish', 'Buffer' => $BufferJSON)));
    }

    public function setPowerOnState(int $value)
    {
        $command = 'PowerOnState';
        $msg = $value;
        $BufferJSON = $this->MQTTCommand($command, $msg);
        $this->SendDebug('setPowerOnState', $BufferJSON, 0);
        $this->SendDataToParent(json_encode(array('DataID' => '{018EF6B5-AB94-40C6-AA53-46943E824ACF}', 'Action' => 'Publish', 'Buffer' => $BufferJSON)));
    }

    public function setPower(int $power, bool $Value)
    {
        //$this->defineLanguage($this->ReadPropertyString("DeviceLanguage"));
        if ($power != 0) {
            $PowerIdent = 'Tasmota_POWER' . strval($power);
            $powerTopic = 'POWER' . strval($power);
        } else {
            $PowerIdent = 'Tasmota_POWER';
            $powerTopic = 'POWER';
        }
        $command = $powerTopic;
        $msg = $Value;
        if ($msg === false) {
            $msg = 'OFF';
        } elseif ($msg === true) {
            $msg = 'ON';
        }
        $BufferJSON = $this->MQTTCommand($command, $msg);
        $this->SendDebug(__FUNCTION__, $BufferJSON, 0);
        $this->SendDataToParent(json_encode(array('DataID' => '{018EF6B5-AB94-40C6-AA53-46943E824ACF}', 'Action' => 'Publish', 'Buffer' => $BufferJSON)));
    }

    //Für Sensoren
    protected function find_parent($array, $needle, $parent = null)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $pass = $parent;
                if (is_string($key)) {
                    $pass = $key;
                }
                $found = $this->find_parent($value, $needle, $pass);
                if ($found !== false) {
                    return $found;
                }
            } elseif ($value === $needle) {
                return $parent;
            }
        }
        return false;
    }
    protected function find_address($array, $needle, $parent = null)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $pass = $parent;
                if (is_string($key)) {
                    $pass = $key;
                }
                $found = $this->find_parent($value, $needle, $pass);
                if ($found !== false) {
                    return $found;
                }
            } elseif ($value === $needle) {
                return $parent;
            }
        }
        return false;
    }
    protected function traverseArray($array, $GesamtArray)
    {
        foreach ($array as $key=> $value) {
            if (is_array($value)) {
                $this->traverseArray($value, $GesamtArray);
            } else {
                $ParentKey = $this->find_parent($GesamtArray, $value);
                $this->SendDebug('Rekursion Tasmota ' . $ParentKey . '_' . $key, "$key = $value", 0);
                if($key=='Address') {
                    //$address = str_replace('-', '_', $key);
                    $address = $value;
                    $this->SendDebug('Adresse ', "$address", 0);
                }
                if (is_int($value) or is_float($value)) {
                    $ParentKey = str_replace('-', '_', $ParentKey);
                    $key = str_replace('-', '_', $key);
                    $this->SendDebug('gefunden ' . $ParentKey . '_' . $key, "$key = $value", 0);
                    
                    switch ($key) {
                        case 'Temperature':
                            $variablenID = $this->RegisterVariableFloat('Tasmota_' . $ParentKey . '_' . $address, $ParentKey . ' Temperatur', '~Temperature');
                            SetValue($this->GetIDForIdent('Tasmota_' . $ParentKey . '_' . $address), $value);
                            break;
                        case 'Humidity':
                            $variablenID = $this->RegisterVariableFloat('Tasmota_' . $ParentKey . '_' . $key, $ParentKey . ' Feuchte', '~Humidity.F');
                            SetValue($this->GetIDForIdent('Tasmota_' . $ParentKey . '_' . $key), $value);
                            break;
                        default:
                            if ($ParentKey != 'ENERGY') {
                                $variablenID = $this->RegisterVariableFloat('Tasmota_' . $ParentKey . '_' . $key, $ParentKey . ' ' . $key);
                                SetValue($this->GetIDForIdent('Tasmota_' . $ParentKey . '_' . $key), $value);
                            }
                    }
                }
            }
        }
    }
}
