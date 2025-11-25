<?php
header('Content-Type: application/json');

// Philippine provinces and their cities/municipalities
$locations = [
    "Metro Manila" => [
        "Caloocan", "Las Piñas", "Makati", "Malabon", "Mandaluyong", "Manila", 
        "Marikina", "Muntinlupa", "Navotas", "Parañaque", "Pasay", "Pasig", 
        "Quezon City", "San Juan", "Taguig", "Valenzuela", "Pateros"
    ],
    "Abra" => ["Bangued", "Boliney", "Bucay", "Bucloc", "Daguioman", "Danglas", "Dolores", "La Paz", "Lacub", "Lagangilang", "Lagayan", "Langiden", "Licuan-Baay", "Luba", "Malibcong", "Manabo", "Peñarrubia", "Pidigan", "Pilar", "Sallapadan", "San Isidro", "San Juan", "San Quintin", "Tayum", "Tineg", "Tubo", "Villaviciosa"],
    "Agusan del Norte" => ["Butuan", "Cabadbaran", "Buenavista", "Carmen", "Jabonga", "Kitcharao", "Las Nieves", "Magallanes", "Nasipit", "Remedios T. Romualdez", "Santiago", "Tubay"],
    "Agusan del Sur" => ["Bayugan", "Bunawan", "Esperanza", "La Paz", "Loreto", "Prosperidad", "Rosario", "San Francisco", "San Luis", "Santa Josefa", "Sibagat", "Talacogon", "Trento", "Veruela"],
    "Aklan" => ["Altavas", "Balete", "Banga", "Batan", "Buruanga", "Ibajay", "Kalibo", "Lezo", "Libacao", "Madalag", "Makato", "Malay", "Malinao", "Nabas", "New Washington", "Numancia", "Tangalan"],
    "Albay" => ["Legazpi", "Ligao", "Tabaco", "Bacacay", "Camalig", "Daraga", "Guinobatan", "Jovellar", "Libon", "Malilipot", "Malinao", "Manito", "Oas", "Pio Duran", "Polangui", "Rapu-Rapu", "Santo Domingo", "Tiwi"],
    "Antique" => ["San Jose de Buenavista", "Anini-y", "Barbaza", "Belison", "Bugasong", "Caluya", "Culasi", "Hamtic", "Laua-an", "Libertad", "Pandan", "Patnongon", "San Remigio", "Sebaste", "Sibalom", "Tibiao", "Tobias Fornier", "Valderrama"],
    "Apayao" => ["Calanasan", "Conner", "Flora", "Kabugao", "Luna", "Pudtol", "Santa Marcela"],
    "Aurora" => ["Baler", "Casiguran", "Dilasag", "Dinalungan", "Dingalan", "Dipaculao", "Maria Aurora", "San Luis"],
    "Basilan" => ["Isabela City", "Lamitan", "Akbar", "Al-Barka", "Hadji Mohammad Ajul", "Hadji Muhtamad", "Lantawan", "Maluso", "Sumisip", "Tabuan-Lasa", "Tipo-Tipo", "Tuburan", "Ungkaya Pukan"],
    "Bataan" => ["Balanga", "Abucay", "Bagac", "Dinalupihan", "Hermosa", "Limay", "Mariveles", "Morong", "Orani", "Orion", "Pilar", "Samal"],
    "Batanes" => ["Basco", "Itbayat", "Ivana", "Mahatao", "Sabtang", "Uyugan"],
    "Batangas" => ["Batangas City", "Lipa", "Tanauan", "Agoncillo", "Alitagtag", "Balayan", "Balete", "Bauan", "Calaca", "Calatagan", "Cuenca", "Ibaan", "Laurel", "Lemery", "Lian", "Lobo", "Mabini", "Malvar", "Mataasnakahoy", "Nasugbu", "Padre Garcia", "Rosario", "San Jose", "San Juan", "San Luis", "San Nicolas", "San Pascual", "Santa Teresita", "Santo Tomas", "Taal", "Talisay", "Taysan", "Tingloy", "Tuy"],
    "Benguet" => ["Baguio", "Atok", "Bakun", "Bokod", "Buguias", "Itogon", "Kabayan", "Kapangan", "Kibungan", "La Trinidad", "Mankayan", "Sablan", "Tuba", "Tublay"],
    "Biliran" => ["Almeria", "Biliran", "Cabucgayan", "Caibiran", "Culaba", "Kawayan", "Maripipi", "Naval"],
    "Bohol" => ["Tagbilaran", "Alburquerque", "Alicia", "Anda", "Antequera", "Baclayon", "Balilihan", "Batuan", "Bien Unido", "Bilar", "Buenavista", "Calape", "Candijay", "Carmen", "Catigbian", "Clarin", "Corella", "Cortes", "Dagohoy", "Danao", "Dauis", "Dimiao", "Duero", "Garcia Hernandez", "Getafe", "Guindulman", "Inabanga", "Jagna", "Lila", "Loay", "Loboc", "Loon", "Mabini", "Maribojoc", "Panglao", "Pilar", "President Carlos P. Garcia", "Sagbayan", "San Isidro", "San Miguel", "Sevilla", "Sierra Bullones", "Sikatuna", "Talibon", "Trinidad", "Tubigon", "Ubay", "Valencia"],
    "Bukidnon" => ["Malaybalay", "Valencia", "Baungon", "Cabanglasan", "Damulog", "Dangcagan", "Don Carlos", "Impasugong", "Kadingilan", "Kalilangan", "Kibawe", "Kitaotao", "Lantapan", "Libona", "Malitbog", "Manolo Fortich", "Maramag", "Pangantucan", "Quezon", "San Fernando", "Sumilao", "Talakag"],
    "Bulacan" => ["Malolos", "Meycauayan", "San Jose del Monte", "Angat", "Balagtas", "Baliuag", "Bocaue", "Bulakan", "Bustos", "Calumpit", "Doña Remedios Trinidad", "Guiguinto", "Hagonoy", "Marilao", "Norzagaray", "Obando", "Pandi", "Paombong", "Plaridel", "Pulilan", "San Ildefonso", "San Miguel", "San Rafael", "Santa Maria"],
    "Cagayan" => ["Tuguegarao", "Abulug", "Alcala", "Allacapan", "Amulung", "Aparri", "Baggao", "Ballesteros", "Buguey", "Calayan", "Camalaniugan", "Claveria", "Enrile", "Gattaran", "Gonzaga", "Iguig", "Lal-lo", "Lasam", "Pamplona", "Peñablanca", "Piat", "Rizal", "Sanchez-Mira", "Santa Ana", "Santa Praxedes", "Santa Teresita", "Santo Niño", "Solana", "Tuao"],
    "Camarines Norte" => ["Basud", "Capalonga", "Daet", "Jose Panganiban", "Labo", "Mercedes", "Paracale", "San Lorenzo Ruiz", "San Vicente", "Santa Elena", "Talisay", "Vinzons"],
    "Camarines Sur" => ["Iriga", "Naga", "Baao", "Balatan", "Bato", "Bombon", "Buhi", "Bula", "Cabusao", "Calabanga", "Camaligan", "Canaman", "Caramoan", "Del Gallego", "Gainza", "Garchitorena", "Goa", "Lagonoy", "Libmanan", "Lupi", "Magarao", "Milaor", "Minalabac", "Nabua", "Ocampo", "Pamplona", "Pasacao", "Pili", "Presentacion", "Ragay", "Sagñay", "San Fernando", "San Jose", "Sipocot", "Siruma", "Tigaon", "Tinambac"],
    "Camiguin" => ["Catarman", "Guinsiliban", "Mahinog", "Mambajao", "Sagay"],
    "Capiz" => ["Roxas", "Cuartero", "Dao", "Dumalag", "Dumarao", "Ivisan", "Jamindan", "Ma-ayon", "Mambusao", "Panay", "Panitan", "Pilar", "Pontevedra", "President Roxas", "Sapi-an", "Sigma", "Tapaz"],
    "Catanduanes" => ["Bagamanoc", "Baras", "Bato", "Caramoran", "Gigmoto", "Pandan", "Panganiban", "San Andres", "San Miguel", "Viga", "Virac"],
    "Cavite" => ["Bacoor", "Cavite City", "Dasmariñas", "General Trias", "Imus", "Tagaytay", "Trece Martires", "Alfonso", "Amadeo", "Carmona", "General Emilio Aguinaldo", "General Mariano Alvarez", "Indang", "Kawit", "Magallanes", "Maragondon", "Mendez", "Naic", "Noveleta", "Rosario", "Silang", "Tanza", "Ternate"],
    "Cebu" => ["Cebu City", "Lapu-Lapu", "Mandaue", "Alcantara", "Alcoy", "Alegria", "Aloguinsan", "Argao", "Asturias", "Badian", "Balamban", "Bantayan", "Barili", "Bogo", "Boljoon", "Borbon", "Carcar", "Carmen", "Catmon", "Compostela", "Consolacion", "Cordova", "Daanbantayan", "Dalaguete", "Danao", "Dumanjug", "Ginatilan", "Liloan", "Madridejos", "Malabuyoc", "Medellin", "Minglanilla", "Moalboal", "Naga", "Oslob", "Pilar", "Pinamungajan", "Poro", "Ronda", "Samboan", "San Fernando", "San Francisco", "San Remigio", "Santa Fe", "Santander", "Sibonga", "Sogod", "Tabogon", "Tabuelan", "Talisay", "Toledo", "Tuburan", "Tudela"],
    "Cotabato" => ["Kidapawan", "Alamada", "Aleosan", "Antipas", "Arakan", "Banisilan", "Carmen", "Kabacan", "Libungan", "M'lang", "Magpet", "Makilala", "Matalam", "Midsayap", "Pigcawayan", "Pikit", "President Roxas", "Tulunan"],
    "Davao de Oro" => ["Nabunturan", "Compostela", "Laak", "Mabini", "Maco", "Maragusan", "Mawab", "Monkayo", "Montevista", "New Bataan", "Pantukan"],
    "Davao del Norte" => ["Tagum", "Asuncion", "Braulio E. Dujali", "Carmen", "Kapalong", "New Corella", "Panabo", "Samal", "San Isidro", "Santo Tomas", "Talaingod"],
    "Davao del Sur" => ["Digos", "Bansalan", "Hagonoy", "Kiblawan", "Magsaysay", "Malalag", "Matanao", "Padada", "Santa Cruz", "Sulop"],
    "Davao Occidental" => ["Malita", "Don Marcelino", "Jose Abad Santos", "Santa Maria", "Sarangani"],
    "Davao Oriental" => ["Mati", "Baganga", "Banaybanay", "Boston", "Caraga", "Cateel", "Governor Generoso", "Lupon", "Manay", "San Isidro", "Tarragona"],
    "Dinagat Islands" => ["Basilisa", "Cagdianao", "Dinagat", "Libjo", "Loreto", "San Jose", "Tubajon"],
    "Eastern Samar" => ["Borongan", "Arteche", "Balangiga", "Balangkayan", "Can-avid", "Dolores", "General MacArthur", "Giporlos", "Guiuan", "Hernani", "Jipapad", "Lawaan", "Llorente", "Maslog", "Maydolong", "Mercedes", "Oras", "Quinapondan", "Salcedo", "San Julian", "San Policarpo", "Sulat", "Taft"],
    "Guimaras" => ["Buenavista", "Jordan", "Nueva Valencia", "San Lorenzo", "Sibunag"],
    "Ifugao" => ["Aguinaldo", "Alfonso Lista", "Asipulo", "Banaue", "Hingyon", "Hungduan", "Kiangan", "Lagawe", "Lamut", "Mayoyao", "Tinoc"],
    "Ilocos Norte" => ["Laoag", "Batac", "Adams", "Bacarra", "Badoc", "Bangui", "Banna", "Burgos", "Carasi", "Currimao", "Dingras", "Dumalneg", "Marcos", "Nueva Era", "Pagudpud", "Paoay", "Pasuquin", "Piddig", "Pinili", "San Nicolas", "Sarrat", "Solsona", "Vintar"],
    "Ilocos Sur" => ["Vigan", "Candon", "Alilem", "Banayoyo", "Bantay", "Burgos", "Cabugao", "Caoayan", "Cervantes", "Galimuyod", "Gregorio del Pilar", "Lidlidda", "Magsingal", "Nagbukel", "Narvacan", "Quirino", "Salcedo", "San Emilio", "San Esteban", "San Ildefonso", "San Juan", "San Vicente", "Santa", "Santa Catalina", "Santa Cruz", "Santa Lucia", "Santa Maria", "Santiago", "Santo Domingo", "Sigay", "Sinait", "Sugpon", "Suyo", "Tagudin"],
    "Iloilo" => ["Iloilo City", "Passi", "Ajuy", "Alimodian", "Anilao", "Badiangan", "Balasan", "Banate", "Barotac Nuevo", "Barotac Viejo", "Batad", "Bingawan", "Cabatuan", "Calinog", "Carles", "Concepcion", "Dingle", "Dueñas", "Dumangas", "Estancia", "Guimbal", "Igbaras", "Janiuay", "Lambunao", "Leganes", "Lemery", "Leon", "Maasin", "Miagao", "Mina", "New Lucena", "Oton", "Pavia", "Pototan", "San Dionisio", "San Enrique", "San Joaquin", "San Miguel", "San Rafael", "Santa Barbara", "Sara", "Tigbauan", "Tubungan", "Zarraga"],
    "Isabela" => ["Ilagan", "Cauayan", "Santiago", "Alicia", "Angadanan", "Aurora", "Benito Soliven", "Burgos", "Cabagan", "Cabatuan", "Cordon", "Delfin Albano", "Dinapigue", "Divilacan", "Echague", "Gamu", "Jones", "Luna", "Maconacon", "Mallig", "Naguilian", "Palanan", "Quezon", "Quirino", "Ramon", "Reina Mercedes", "Roxas", "San Agustin", "San Guillermo", "San Isidro", "San Manuel", "San Mariano", "San Mateo", "San Pablo", "Santa Maria", "Santo Tomas", "Tumauini"],
    "Kalinga" => ["Balbalan", "Lubuagan", "Pasil", "Pinukpuk", "Rizal", "Tabuk", "Tanudan", "Tinglayan"],
    "La Union" => ["San Fernando", "Agoo", "Aringay", "Bacnotan", "Bagulin", "Balaoan", "Bangar", "Bauang", "Burgos", "Caba", "Luna", "Naguilian", "Pugo", "Rosario", "San Gabriel", "San Juan", "Santo Tomas", "Santol", "Sudipen", "Tubao"],
    "Laguna" => ["Biñan", "Cabuyao", "Calamba", "San Pablo", "San Pedro", "Santa Rosa", "Alaminos", "Bay", "Calauan", "Cavinti", "Famy", "Kalayaan", "Liliw", "Los Baños", "Luisiana", "Lumban", "Mabitac", "Magdalena", "Majayjay", "Nagcarlan", "Paete", "Pagsanjan", "Pakil", "Pangil", "Pila", "Rizal", "San Juan", "Santa Cruz", "Santa Maria", "Siniloan", "Victoria"],
    "Lanao del Norte" => ["Iligan", "Bacolod", "Baloi", "Baroy", "Kapatagan", "Kauswagan", "Kolambugan", "Lala", "Linamon", "Magsaysay", "Maigo", "Matungao", "Munai", "Nunungan", "Pantao Ragat", "Pantar", "Poona Piagapo", "Salvador", "Sapad", "Sultan Naga Dimaporo", "Tagoloan", "Tangcal", "Tubod"],
    "Lanao del Sur" => ["Marawi", "Bacolod-Kalawi", "Balabagan", "Balindong", "Bayang", "Binidayan", "Buadiposo-Buntong", "Bubong", "Butig", "Calanogas", "Ditsaan-Ramain", "Ganassi", "Kapai", "Kapatagan", "Lumba-Bayabao", "Lumbaca-Unayan", "Lumbatan", "Lumbayanague", "Madalum", "Madamba", "Maguing", "Malabang", "Marantao", "Marogong", "Masiu", "Mulondo", "Pagayawan", "Piagapo", "Picong", "Poona Bayabao", "Pualas", "Saguiaran", "Sultan Dumalondong", "Tagoloan II", "Tamparan", "Taraka", "Tubaran", "Tugaya", "Wao"],
    "Leyte" => ["Tacloban", "Ormoc", "Abuyog", "Alangalang", "Albuera", "Babatngon", "Barugo", "Bato", "Baybay", "Burauen", "Calubian", "Capoocan", "Carigara", "Dagami", "Dulag", "Hilongos", "Hindang", "Inopacan", "Isabel", "Jaro", "Javier", "Julita", "Kananga", "La Paz", "Leyte", "MacArthur", "Mahaplag", "Matag-ob", "Matalom", "Mayorga", "Merida", "Palo", "Palompon", "Pastrana", "San Isidro", "San Miguel", "Santa Fe", "Tabango", "Tabontabon", "Tanauan", "Tolosa", "Tunga", "Villaba"],
    "Maguindanao" => ["Ampatuan", "Barira", "Buldon", "Buluan", "Datu Abdullah Sangki", "Datu Anggal Midtimbang", "Datu Blah T. Sinsuat", "Datu Hoffer Ampatuan", "Datu Montawal", "Datu Odin Sinsuat", "Datu Paglas", "Datu Piang", "Datu Salibo", "Datu Saudi-Ampatuan", "Datu Unsay", "Gen. S. K. Pendatun", "Guindulungan", "Kabuntalan", "Mamasapano", "Mangudadatu", "Matanog", "Northern Kabuntalan", "Pagalungan", "Paglat", "Pandag", "Parang", "Rajah Buayan", "Shariff Aguak", "Shariff Saydona Mustapha", "South Upi", "Sultan Kudarat", "Sultan Mastura", "Sultan sa Barongis", "Sultan Sumagka", "Talayan", "Talitay", "Upi"],
    "Marinduque" => ["Boac", "Buenavista", "Gasan", "Mogpog", "Santa Cruz", "Torrijos"],
    "Masbate" => ["Masbate City", "Aroroy", "Baleno", "Balud", "Batuan", "Cataingan", "Cawayan", "Claveria", "Dimasalang", "Esperanza", "Mandaon", "Milagros", "Mobo", "Monreal", "Palanas", "Pio V. Corpuz", "Placer", "San Fernando", "San Jacinto", "San Pascual", "Uson"],
    "Misamis Occidental" => ["Oroquieta", "Ozamiz", "Tangub", "Aloran", "Baliangao", "Bonifacio", "Calamba", "Clarin", "Concepcion", "Don Victoriano Chiongbian", "Jimenez", "Lopez Jaena", "Panaon", "Plaridel", "Sapang Dalaga", "Sinacaban", "Tudela"],
    "Misamis Oriental" => ["Cagayan de Oro", "Gingoog", "Alubijid", "Balingasag", "Balingoan", "Binuangan", "Claveria", "El Salvador", "Gitagum", "Initao", "Jasaan", "Kinoguitan", "Lagonglong", "Laguindingan", "Libertad", "Lugait", "Magsaysay", "Manticao", "Medina", "Naawan", "Opol", "Salay", "Sugbongcogon", "Tagoloan", "Talisayan", "Villanueva"],
    "Mountain Province" => ["Barlig", "Bauko", "Besao", "Bontoc", "Natonin", "Paracelis", "Sabangan", "Sadanga", "Sagada", "Tadian"],
    "Negros Occidental" => ["Bacolod", "Bago", "Cadiz", "Escalante", "Himamaylan", "Kabankalan", "La Carlota", "Sagay", "San Carlos", "Silay", "Sipalay", "Talisay", "Victorias", "Binalbagan", "Calatrava", "Candoni", "Cauayan", "Enrique B. Magalona", "Hinigaran", "Hinoba-an", "Ilog", "Isabela", "La Castellana", "Manapla", "Moises Padilla", "Murcia", "Pontevedra", "Pulupandan", "Salvador Benedicto", "San Enrique", "Toboso", "Valladolid"],
    "Negros Oriental" => ["Dumaguete", "Bais", "Bayawan", "Canlaon", "Guihulngan", "Tanjay", "Amlan", "Ayungon", "Bacong", "Basay", "Bindoy", "Dauin", "Jimalalud", "La Libertad", "Mabinay", "Manjuyod", "Pamplona", "San Jose", "Santa Catalina", "Siaton", "Sibulan", "Tayasan", "Valencia", "Vallehermoso", "Zamboanguita"],
    "Northern Samar" => ["Allen", "Biri", "Bobon", "Capul", "Catarman", "Catubig", "Gamay", "Laoang", "Lapinig", "Las Navas", "Lavezares", "Lope de Vega", "Mapanas", "Mondragon", "Palapag", "Pambujan", "Rosario", "San Antonio", "San Isidro", "San Jose", "San Roque", "San Vicente", "Silvino Lobos", "Victoria"],
    "Nueva Ecija" => ["Cabanatuan", "Gapan", "Palayan", "San Jose", "Science City of Muñoz", "Aliaga", "Bongabon", "Cabiao", "Carranglan", "Cuyapo", "Gabaldon", "General Mamerto Natividad", "General Tinio", "Guimba", "Jaen", "Laur", "Licab", "Llanera", "Lupao", "Nampicuan", "Pantabangan", "Peñaranda", "Quezon", "Rizal", "San Antonio", "San Isidro", "San Leonardo", "Santa Rosa", "Santo Domingo", "Talavera", "Talugtug", "Zaragoza"],
    "Nueva Vizcaya" => ["Bayombong", "Alfonso Castaneda", "Ambaguio", "Aritao", "Bagabag", "Bambang", "Diadi", "Dupax del Norte", "Dupax del Sur", "Kasibu", "Kayapa", "Quezon", "Santa Fe", "Solano", "Villaverde"],
    "Occidental Mindoro" => ["Abra de Ilog", "Calintaan", "Looc", "Lubang", "Magsaysay", "Mamburao", "Paluan", "Rizal", "Sablayan", "San Jose", "Santa Cruz"],
    "Oriental Mindoro" => ["Calapan", "Baco", "Bansud", "Bongabong", "Bulalacao", "Gloria", "Mansalay", "Naujan", "Pinamalayan", "Pola", "Puerto Galera", "Roxas", "San Teodoro", "Socorro", "Victoria"],
    "Palawan" => ["Puerto Princesa", "Aborlan", "Agutaya", "Araceli", "Balabac", "Bataraza", "Brooke's Point", "Busuanga", "Cagayancillo", "Coron", "Culion", "Cuyo", "Dumaran", "El Nido", "Kalayaan", "Linapacan", "Magsaysay", "Narra", "Quezon", "Rizal", "Roxas", "San Vicente", "Sofronio Española", "Taytay"],
    "Pampanga" => ["Angeles", "Mabalacat", "San Fernando", "Apalit", "Arayat", "Bacolor", "Candaba", "Floridablanca", "Guagua", "Lubao", "Macabebe", "Magalang", "Masantol", "Mexico", "Minalin", "Porac", "San Luis", "San Simon", "Santa Ana", "Santa Rita", "Santo Tomas", "Sasmuan"],
    "Pangasinan" => ["Alaminos", "Dagupan", "San Carlos", "Urdaneta", "Agno", "Aguilar", "Alcala", "Anda", "Asingan", "Balungao", "Bani", "Basista", "Bautista", "Bayambang", "Binalonan", "Binmaley", "Bolinao", "Bugallon", "Burgos", "Calasiao", "Dasol", "Infanta", "Labrador", "Laoac", "Lingayen", "Mabini", "Malasiqui", "Manaoag", "Mangaldan", "Mangatarem", "Mapandan", "Natividad", "Pozorrubio", "Rosales", "San Fabian", "San Jacinto", "San Manuel", "San Nicolas", "San Quintin", "Santa Barbara", "Santa Maria", "Santo Tomas", "Sison", "Sual", "Tayug", "Umingan", "Urbiztondo", "Villasis"],
    "Quezon" => ["Lucena", "Tayabas", "Agdangan", "Alabat", "Atimonan", "Buenavista", "Burdeos", "Calauag", "Candelaria", "Catanauan", "Dolores", "General Luna", "General Nakar", "Guinayangan", "Gumaca", "Infanta", "Jomalig", "Lopez", "Lucban", "Macalelon", "Mauban", "Mulanay", "Padre Burgos", "Pagbilao", "Panukulan", "Patnanungan", "Perez", "Pitogo", "Plaridel", "Polillo", "Quezon", "Real", "Sampaloc", "San Andres", "San Antonio", "San Francisco", "San Narciso", "Sariaya", "Tagkawayan", "Tiaong", "Unisan"],
    "Quirino" => ["Aglipay", "Cabarroguis", "Diffun", "Maddela", "Nagtipunan", "Saguday"],
    "Rizal" => ["Antipolo", "Angono", "Baras", "Binangonan", "Cainta", "Cardona", "Jalajala", "Morong", "Pililla", "Rodriguez", "San Mateo", "Tanay", "Taytay", "Teresa"],
    "Romblon" => ["Alcantara", "Banton", "Cajidiocan", "Calatrava", "Concepcion", "Corcuera", "Ferrol", "Looc", "Magdiwang", "Odiongan", "Romblon", "San Agustin", "San Andres", "San Fernando", "San Jose", "Santa Fe", "Santa Maria"],
    "Samar" => ["Calbayog", "Catbalogan", "Almagro", "Basey", "Calbiga", "Daram", "Gandara", "Hinabangan", "Jiabong", "Marabut", "Matuguinao", "Motiong", "Pagsanghan", "Paranas", "Pinabacdao", "San Jorge", "San Jose de Buan", "San Sebastian", "Santa Margarita", "Santa Rita", "Santo Niño", "Tagapul-an", "Talalora", "Tarangnan", "Villareal", "Zumarraga"],
    "Sarangani" => ["Alabel", "Glan", "Kiamba", "Maasim", "Maitum", "Malapatan", "Malungon"],
    "Siquijor" => ["Enrique Villanueva", "Larena", "Lazi", "Maria", "San Juan", "Siquijor"],
    "Sorsogon" => ["Sorsogon City", "Barcelona", "Bulan", "Bulusan", "Casiguran", "Castilla", "Donsol", "Gubat", "Irosin", "Juban", "Magallanes", "Matnog", "Pilar", "Prieto Diaz", "Santa Magdalena"],
    "South Cotabato" => ["General Santos", "Koronadal", "Banga", "Lake Sebu", "Norala", "Polomolok", "Santo Niño", "Surallah", "T'Boli", "Tampakan", "Tantangan", "Tupi"],
    "Southern Leyte" => ["Maasin", "Anahawan", "Bontoc", "Hinunangan", "Hinundayan", "Libagon", "Liloan", "Limasawa", "Macrohon", "Malitbog", "Padre Burgos", "Pintuyan", "Saint Bernard", "San Francisco", "San Juan", "San Ricardo", "Silago", "Sogod", "Tomas Oppus"],
    "Sultan Kudarat" => ["Tacurong", "Bagumbayan", "Columbio", "Esperanza", "Isulan", "Kalamansig", "Lambayong", "Lebak", "Lutayan", "Palimbang", "President Quirino", "Senator Ninoy Aquino"],
    "Sulu" => ["Hadji Panglima Tahil", "Indanan", "Jolo", "Kalingalan Caluang", "Lugus", "Luuk", "Maimbung", "Old Panamao", "Omar", "Pandami", "Panglima Estino", "Pangutaran", "Parang", "Pata", "Patikul", "Siasi", "Talipao", "Tapul", "Tongkil"],
    "Surigao del Norte" => ["Surigao City", "Alegria", "Bacuag", "Burgos", "Claver", "Dapa", "Del Carmen", "General Luna", "Gigaquit", "Mainit", "Malimono", "Pilar", "Placer", "San Benito", "San Francisco", "San Isidro", "Santa Monica", "Sison", "Socorro", "Tagana-an", "Tubod"],
    "Surigao del Sur" => ["Bislig", "Tandag", "Barobo", "Bayabas", "Cagwait", "Cantilan", "Carmen", "Carrascal", "Cortes", "Hinatuan", "Lanuza", "Lianga", "Lingig", "Madrid", "Marihatag", "San Agustin", "San Miguel", "Tagbina", "Tago"],
    "Tarlac" => ["Tarlac City", "Anao", "Bamban", "Camiling", "Capas", "Concepcion", "Gerona", "La Paz", "Mayantoc", "Moncada", "Paniqui", "Pura", "Ramos", "San Clemente", "San Jose", "San Manuel", "Santa Ignacia", "Victoria"],
    "Tawi-Tawi" => ["Bongao", "Languyan", "Mapun", "Panglima Sugala", "Sapa-Sapa", "Sibutu", "Simunul", "Sitangkai", "South Ubian", "Tandubas", "Turtle Islands"],
    "Zambales" => ["Olongapo", "Botolan", "Cabangan", "Candelaria", "Castillejos", "Iba", "Masinloc", "Palauig", "San Antonio", "San Felipe", "San Marcelino", "San Narciso", "Santa Cruz", "Subic"],
    "Zamboanga del Norte" => ["Dapitan", "Dipolog", "Baliguian", "Godod", "Gutalac", "Jose Dalman", "Kalawit", "Katipunan", "La Libertad", "Labason", "Leon B. Postigo", "Liloy", "Manukan", "Mutia", "Piñan", "Polanco", "President Manuel A. Roxas", "Rizal", "Salug", "Sergio Osmeña Sr.", "Siayan", "Sibuco", "Sibutad", "Sindangan", "Siocon", "Sirawai", "Tampilisan"],
    "Zamboanga del Sur" => ["Pagadian", "Zamboanga City", "Aurora", "Bayog", "Dimataling", "Dinas", "Dumalinao", "Dumingag", "Guipos", "Josefina", "Kumalarang", "Labangan", "Lakewood", "Lapuyan", "Mahayag", "Margosatubig", "Midsalip", "Molave", "Pitogo", "Ramon Magsaysay", "San Miguel", "San Pablo", "Sominot", "Tabina", "Tambulig", "Tigbao", "Tukuran", "Vincenzo A. Sagun"],
    "Zamboanga Sibugay" => ["Alicia", "Buug", "Diplahan", "Imelda", "Ipil", "Kabasalan", "Mabuhay", "Malangas", "Naga", "Olutanga", "Payao", "Roseller Lim", "Siay", "Talusan", "Titay", "Tungawan"]
];

// Barangays by City (sample data for major cities - expand as needed)
$barangays = [
    "Quezon City" => [
        "Bagong Pag-asa", "Bahay Toro", "Balingasa", "Batasan Hills", "Commonwealth",
        "Culiat", "Fairview", "Kamuning", "Libis", "Loyola Heights", "Novaliches",
        "Project 4", "Project 6", "Project 8", "San Antonio", "Santa Mesa Heights",
        "Tandang Sora", "Teachers Village", "UP Campus", "White Plains"
    ],
    "Makati" => [
        "Bel-Air", "Cembo", "Comembo", "Dasmariñas", "Forbes Park", "Guadalupe Nuevo",
        "Guadalupe Viejo", "Kasilawan", "La Paz", "Magallanes", "Olympia", "Palanan",
        "Pembo", "Pinagkaisahan", "Pio del Pilar", "Poblacion", "Rockwell", "San Antonio",
        "San Isidro", "San Lorenzo", "Santa Cruz", "Singkamas", "Tejeros", "Urdaneta", "Valenzuela"
    ],
    "Manila" => [
        "Binondo", "Ermita", "Intramuros", "Malate", "Paco", "Pandacan", "Port Area",
        "Quiapo", "Sampaloc", "San Miguel", "San Nicolas", "Santa Ana", "Santa Cruz",
        "Santa Mesa", "Tondo"
    ],
    "Pasig" => [
        "Bagong Ilog", "Bagong Katipunan", "Bambang", "Buting", "Caniogan", "Dela Paz",
        "Kalawaan", "Kapasigan", "Kapitolyo", "Malinao", "Manggahan", "Maybunga",
        "Oranbo", "Palatiw", "Pinagbuhatan", "Pineda", "Rosario", "Sagad", "San Antonio",
        "San Joaquin", "San Jose", "San Miguel", "San Nicolas", "Santa Cruz", "Santa Lucia",
        "Santa Rosa", "Santo Tomas", "Santolan", "Sumilang", "Ugong"
    ],
    "Taguig" => [
        "Bagumbayan", "Bambang", "Calzada", "Central Bicutan", "Central Signal Village",
        "Fort Bonifacio", "Hagonoy", "Ibayo-Tipas", "Katuparan", "Ligid-Tipas",
        "Lower Bicutan", "Maharlika Village", "Napindan", "New Lower Bicutan", "North Signal Village",
        "Palingon", "Pinagsama", "San Miguel", "Santa Ana", "Tanyag", "Tuktukan",
        "Upper Bicutan", "Ususan", "Wawa", "Western Bicutan"
    ],
    "Parañaque" => [
        "Baclaran", "BF Homes", "Don Bosco", "Don Galo", "La Huerta", "Marcelo Green",
        "Merville", "Moonwalk", "San Antonio", "San Dionisio", "San Isidro", "San Martin de Porres",
        "Santo Niño", "Sun Valley", "Tambo", "Vitalez"
    ],
    "Cebu City" => [
        "Apas", "Banilad", "Basak", "Busay", "Camputhaw", "Capitol Site", "Guadalupe",
        "Kasambagan", "Lahug", "Mabolo", "Mandaue", "Pardo", "Pit-os", "Talamban", "Tisa"
    ],
    "Davao City" => [
        "Agdao", "Buhangin", "Bunawan", "Calinan", "Catalunan Grande", "Catalunan Pequeño",
        "Matina", "Panacan", "Poblacion", "Talomo", "Toril", "Tugbok"
    ],
    // Bohol Cities
    "Corella" => [
        "Anislag", "Canapnapan", "Cancatac", "Canigaan", "Poblacion", "Sambog", "Tanday"
    ],
    "Tagbilaran" => [
        "Bool", "Booy", "Cabawan", "Cogon", "Dampas", "Dao", "Mansasa", "Poblacion I", 
        "Poblacion II", "Poblacion III", "San Isidro", "Taloto", "Tiptip", "Ubujan"
    ],
    "Baclayon" => [
        "Cambanac", "Dasitam", "Landican", "Laya", "Libertad", "Montaña", "Pamilacan", 
        "Payahan", "Poblacion", "San Isidro", "San Roque", "San Vicente", "Santa Cruz", "Taguihon"
    ]
];

// Zip codes by Barangay (sample data - each barangay has unique zip code)
$barangayZipCodes = [
    // Quezon City Barangays
    "Bagong Pag-asa" => "1105",
    "Bahay Toro" => "1106",
    "Balingasa" => "1115",
    "Batasan Hills" => "1126",
    "Commonwealth" => "1121",
    "Culiat" => "1128",
    "Fairview" => "1118",
    "Kamuning" => "1103",
    "Libis" => "1110",
    "Loyola Heights" => "1108",
    "Novaliches" => "1123",
    "Project 4" => "1109",
    "Project 6" => "1100",
    "Project 8" => "1106",
    "San Antonio" => "1105",
    "Santa Mesa Heights" => "1114",
    "Tandang Sora" => "1116",
    "Teachers Village" => "1101",
    "UP Campus" => "1101",
    "White Plains" => "1110",
    
    // Makati Barangays
    "Bel-Air" => "1209",
    "Cembo" => "1214",
    "Comembo" => "1214",
    "Dasmariñas" => "1207",
    "Forbes Park" => "1220",
    "Guadalupe Nuevo" => "1212",
    "Guadalupe Viejo" => "1212",
    "Kasilawan" => "1211",
    "La Paz" => "1204",
    "Magallanes" => "1232",
    "Olympia" => "1207",
    "Palanan" => "1235",
    "Pembo" => "1214",
    "Pinagkaisahan" => "1213",
    "Pio del Pilar" => "1230",
    "Poblacion" => "1210",
    "Rockwell" => "1200",
    "San Antonio" => "1203",
    "San Isidro" => "1234",
    "San Lorenzo" => "1223",
    "Santa Cruz" => "1205",
    "Singkamas" => "1215",
    "Tejeros" => "1206",
    "Urdaneta" => "1225",
    "Valenzuela" => "1227",
    
    // Manila Barangays
    "Binondo" => "1006",
    "Ermita" => "1000",
    "Intramuros" => "1002",
    "Malate" => "1004",
    "Paco" => "1007",
    "Pandacan" => "1011",
    "Port Area" => "1018",
    "Quiapo" => "1001",
    "Sampaloc" => "1008",
    "San Miguel" => "1005",
    "San Nicolas" => "1010",
    "Santa Ana" => "1009",
    "Santa Cruz" => "1003",
    "Santa Mesa" => "1016",
    "Tondo" => "1013",
    
    // Pasig Barangays
    "Bagong Ilog" => "1600",
    "Bagong Katipunan" => "1602",
    "Bambang" => "1609",
    "Buting" => "1605",
    "Caniogan" => "1606",
    "Dela Paz" => "1603",
    "Kalawaan" => "1607",
    "Kapasigan" => "1601",
    "Kapitolyo" => "1603",
    "Malinao" => "1602",
    "Manggahan" => "1611",
    "Maybunga" => "1607",
    "Oranbo" => "1600",
    "Palatiw" => "1610",
    "Pinagbuhatan" => "1602",
    "Pineda" => "1605",
    "Rosario" => "1609",
    "Sagad" => "1607",
    "San Antonio" => "1605",
    "San Joaquin" => "1601",
    "San Jose" => "1601",
    "San Miguel" => "1604",
    "San Nicolas" => "1602",
    "Santa Cruz" => "1603",
    "Santa Lucia" => "1608",
    "Santa Rosa" => "1609",
    "Santo Tomas" => "1610",
    "Santolan" => "1610",
    "Sumilang" => "1611",
    "Ugong" => "1604",
    
    // Taguig Barangays
    "Bagumbayan" => "1630",
    "Bambang" => "1631",
    "Calzada" => "1632",
    "Central Bicutan" => "1631",
    "Central Signal Village" => "1630",
    "Fort Bonifacio" => "1634",
    "Hagonoy" => "1632",
    "Ibayo-Tipas" => "1630",
    "Katuparan" => "1637",
    "Ligid-Tipas" => "1633",
    "Lower Bicutan" => "1631",
    "Maharlika Village" => "1630",
    "Napindan" => "1630",
    "New Lower Bicutan" => "1632",
    "North Signal Village" => "1630",
    "Palingon" => "1634",
    "Pinagsama" => "1632",
    "San Miguel" => "1630",
    "Santa Ana" => "1632",
    "Tanyag" => "1630",
    "Tuktukan" => "1633",
    "Upper Bicutan" => "1630",
    "Ususan" => "1632",
    "Wawa" => "1632",
    "Western Bicutan" => "1630",
    
    // Parañaque Barangays
    "Baclaran" => "1700",
    "BF Homes" => "1720",
    "Don Bosco" => "1709",
    "Don Galo" => "1714",
    "La Huerta" => "1714",
    "Marcelo Green" => "1714",
    "Merville" => "1711",
    "Moonwalk" => "1708",
    "San Antonio" => "1700",
    "San Dionisio" => "1700",
    "San Isidro" => "1702",
    "San Martin de Porres" => "1700",
    "Santo Niño" => "1700",
    "Sun Valley" => "1711",
    "Tambo" => "1701",
    "Vitalez" => "1706",
    
    // Cebu City Barangays
    "Apas" => "6000",
    "Banilad" => "6014",
    "Basak" => "6000",
    "Busay" => "6000",
    "Camputhaw" => "6000",
    "Capitol Site" => "6000",
    "Guadalupe" => "6000",
    "Kasambagan" => "6000",
    "Lahug" => "6000",
    "Mabolo" => "6000",
    "Mandaue" => "6014",
    "Pardo" => "6000",
    "Pit-os" => "6045",
    "Talamban" => "6000",
    "Tisa" => "6000",
    
    // Davao City Barangays
    "Agdao" => "8000",
    "Buhangin" => "8000",
    "Bunawan" => "8000",
    "Calinan" => "8000",
    "Catalunan Grande" => "8000",
    "Catalunan Pequeño" => "8000",
    "Matina" => "8000",
    "Panacan" => "8000",
    "Poblacion" => "8000",
    "Talomo" => "8000",
    "Toril" => "8000",
    "Tugbok" => "8000",
];

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_provinces') {
    echo json_encode(array_keys($locations));
    
} elseif ($action === 'get_cities') {
    $province = isset($_GET['province']) ? $_GET['province'] : '';
    if (isset($locations[$province])) {
        echo json_encode($locations[$province]);
    } else {
        echo json_encode([]);
    }
    
} elseif ($action === 'get_barangays') {
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    if (isset($barangays[$city])) {
        echo json_encode($barangays[$city]);
    } else {
        // Return empty array if no barangays found for this city
        echo json_encode([]);
    }
    
} elseif ($action === 'get_zipcode') {
    $barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';
    if (isset($barangayZipCodes[$barangay])) {
        echo json_encode(['zipcode' => $barangayZipCodes[$barangay]]);
    } else {
        // Return empty if not found
        echo json_encode(['zipcode' => '']);
    }
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
