<?php

$FunctionList = array();

$FunctionList['filtered_relations'] = array(
    'name'				 => 'filtered_relations',
    'operation_types'	 => array( 'read' ),
    'call_method'		 => array(
        'class'	 => 'MugoObjectRelationsFetchFunctions',
        'method' => 'fetchFunctionFilteredRelations' ),
    'parameter_type'	 => 'standard',
    'parameters'		 => array(
        array(
            'name'		 => 'attribute',
            'type'		 => 'object',
            'required'	 => true
        ),
        array(
            'name'		 => 'filter_by',
            'type'		 => 'array',
            'required'	 => true
        )
    )
);
