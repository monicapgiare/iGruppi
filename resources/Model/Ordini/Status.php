<?php
/**
 * Description of Status
 * Get Status based on date (inizio and fine)
 * 
 * @author gullo
 */
class Model_Ordini_Status {
    
    // Constants for labels
    const STATUS_PIANIFICATO = "Pianificato";
    const STATUS_APERTO = "Aperto";
    const STATUS_CHIUSO = "Chiuso";
    const STATUS_INCONSEGNA = "In Consegna";
    const STATUS_CONSEGNATO = "Consegnato";
    const STATUS_ARCHIVIATO = "Archiviato";
    
    static private $_arStatusOrder = array(
        self::STATUS_PIANIFICATO,
        self::STATUS_APERTO,
        self::STATUS_CHIUSO,
        self::STATUS_INCONSEGNA,
        self::STATUS_CONSEGNATO,
        self::STATUS_ARCHIVIATO
    );
    
    // Private properties for date fields
    private $_inizio;
    private $_fine;
    private $_inconsegna = null;
    private $_consegnato = null;
    private $_archiviato;
    
    public function __construct($o) {
        $this->_inizio = $o->data_inizio;
        $this->_fine = $o->data_fine;
        $this->_inconsegna = $o->data_inconsegna; // CAN BE NULL!
        $this->_consegnato = $o->data_consegnato; // CAN BE NULL!
        $this->_archiviato = $o->archiviato;
    }
    
    public function getStatus() {
        
        if($this->_archiviato != "S") {
            $startObj = $this->getDateObj($this->_inizio);
            $endObj = $this->getDateObj($this->_fine);

            $timestampNow = Zend_Date::now()->toString("U");
            if( $timestampNow < $startObj->toString("U") ) {
                return self::STATUS_PIANIFICATO;
                
            } else if(
                $timestampNow >= $startObj->toString("U") &&
                $timestampNow <= $endObj->toString("U")
            ) {
                return self::STATUS_APERTO;
                
            } else if( 
                $timestampNow > $endObj->toString("U")
            ) {
                
                $inObj = $this->getDateObj($this->_inconsegna);
                $consObj = $this->getDateObj($this->_consegnato);
                
                if( is_null($this->_inconsegna)) {
                    return self::STATUS_CHIUSO;
                } else if(
                    $timestampNow >= $inObj->toString("U") &&
                    is_null($this->_consegnato)
                ) {
                    return self::STATUS_INCONSEGNA;
                } else if(
                    $timestampNow >= $consObj->toString("U")
                ) {
                    return self::STATUS_CONSEGNATO;
                }
                    
            }
        } else {
            return self::STATUS_ARCHIVIATO;
        }
    }
    
    public function getStatusCSSClass()
    {
        return str_replace(" ", "_", $this->getStatus());
    }

    /*
    VERIFICHE STATI
 */   
    public function is_Pianificato() {
        return ($this->getStatus() == self::STATUS_PIANIFICATO);
    }
    public function is_Aperto() {
        return ($this->getStatus() == self::STATUS_APERTO);
    }
    public function is_Chiuso() {
        return ($this->getStatus() == self::STATUS_CHIUSO);
    }
    public function is_InConsegna() {
        return ($this->getStatus() == self::STATUS_INCONSEGNA);
    }
    public function is_Consegnato() {
        return ($this->getStatus() == self::STATUS_CONSEGNATO);
    }
    public function is_Archiviato() {
        return ($this->getStatus() == self::STATUS_ARCHIVIATO);
    }
    

/* ***********************
    PERMESSI STATI
 * *********************** */   
    
    // Se il Referente può modificare i prodotti (prezzi e disponibilità)
    public function canRef_ModificaProdotti() {
        return ( !$this->is_Archiviato() ) ? true : false;
    }    
    // Se il Referente può modificare le Quantità ordinate dagli utenti
    public function canRef_ModificaQtaOrdinate() {
        return ( $this->is_Aperto() || $this->is_Archiviato() ) ? false : true;
    }
    // Se il referente può inviare l'ordine
    public function canRef_InviaOrdine() {
        return $this->is_Chiuso();
    }
    
    
    // Se l'utente può ordinare prodotti
    public function canUser_OrderProducts() {
        return $this->is_Aperto();
    }
    // Se l'utente può visualizzare il Dettaglio di un ordine
    public function canUser_ViewDettaglio() {
        return !$this->is_Pianificato();
    }
    

/* ***************************
 *  MISCELLANEOUS
 *************************** */
    
    private function getDateObj($dt) {
        return new Zend_Date($dt, "y-MM-dd HH:mm:ss");
    }
    
    // get simple array with all status
    static public function getArrayStatus() {
        return self::$_arStatusOrder;
    }
    
    
    // TODO: DA SISTEMARE!
    // Lo so, è orrendo. Piazzato qui è un morto!
    // Ma la logica per identificare lo Stato lasciamola per tutti qui dentro!
    static public function getSqlFilterByStato($stato) {
        switch ($stato)
        {
            case self::STATUS_PIANIFICATO:
                $sql = " AND NOW() < o.data_inizio AND o.archiviato='N'";
                break;

            case self::STATUS_APERTO:
                $sql = " AND NOW() >= o.data_inizio AND NOW() <= o.data_fine AND o.archiviato='N'";
                break;
            
            case self::STATUS_CHIUSO:
                $sql = " AND NOW() > o.data_fine AND NOW() <= o.data_inconsegna AND o.archiviato='N'";
                break;

            case self::STATUS_INCONSEGNA:
                $sql = " AND NOW() > o.data_inconsegna AND NOW() <= o.data_consegnato AND o.archiviato='N'";
                break;

            case self::STATUS_CONSEGNATO:
                $sql = " AND NOW() > o.data_consegnato AND o.archiviato='N'";
                break;

            case self::STATUS_ARCHIVIATO:
                $sql = " AND o.archiviato='S' ";
                break;

        }
        return $sql; 
    }
    
}