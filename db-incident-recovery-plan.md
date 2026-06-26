# load the invoice recipes from the latest dump  and push it into a db dump 

i used the june dumpe as the later on ones are corupted 

# merge them togather o take it step by step and flag what have been changed 

```
update `erb-system`.invoice_recipe erb 
join `dump-001`.invoice_recipe dump on erb.id = dump.id 
Set 
    erb.quantity =  dump.quantity ,
    erb.price =  dump.price ,
    erb.total_price =  dump.total_price ,
    erb.synced = 1  
where dump.price != 0 and dump.total_price != 0;
```

# update the invoice price 

```

update `erb-system`.invoices inv 
join (
    select invoice_id , sum(total_price) as sum 
    from `erb-system`.invoice_recipe 
    group by invoice_id 
) inv_rec on inv.id = inv_rec.invoice_id

set invoice_price = inv_rec.sum, 
total_price =  inv_rec.sum + COALESCE(inv.tax,0) - COALESCE(inv.discount,0);
```


# find  a way to maek the unsynced ones 



i managend to get the commit from the mysqlbinlog  an dparse it to shape a new table 



update `erb-system`.invoice_recipe erb 
join `dump-001`.recipes dump on erb.id = dump.id 
Set 
    erb.quantity =  dump.quantity ,
    erb.price =  dump.price ,
    erb.total_price =  dump.total_price ,
    erb.synced = 1  






# sync icmong withe outgoing 
update invoice_recipe out_rec
join invoice_recipe in_rec
on out_rec.recipe_id  = in_rec.recipe_id 
and in_rec.invoice_id = '01k9ydfpcymztm0q7yz3q2jtkd'
set
 out_rec.quantity =  in_rec.quantity ,
 out_rec.price =  in_rec.price ,
 out_rec.total_price =  in_rec.total_price ,
 out_rec.synced = 1 

 where out_rec.invoice_id = '01ka39cwmeg6j4spe8x63yjt84' 
