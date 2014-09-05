<?php
/**
 * OrdineBuilder builds group for Condivisione ORDINI
 */
class Model_Builder_Sharing_Group_OrdineBuilder 
    extends Model_Builder_Sharing_Group_BuilderInterface
{

    /**
     * @return void
     */
    public function createGroup()
    {
        $this->group = new Model_Builder_Sharing_Group_Parts_Ordine();
    }

}