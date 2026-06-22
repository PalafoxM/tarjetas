CREATE TABLE IF NOT EXISTS establecimiento_backup_20260622_105346 LIKE establecimiento;
DELETE FROM establecimiento_backup_20260622_105346;
INSERT INTO establecimiento_backup_20260622_105346 SELECT * FROM establecimiento;
DROP TABLE IF EXISTS establecimiento_restore_stage;
CREATE TABLE establecimiento_restore_stage LIKE establecimiento;
INSERT INTO `establecimiento_restore_stage`(`id_establecimiento`,`id_tipo`,`no_proveedor`,`dsc_establecimiento`,`direccion`,`telefono`,`ubicacion`,`fec_reg`,`usu_reg`,`fec_act`,`usu_act`,`visible`) values 
(1,1,35863,'RESTAURANTE BAR ARCANGELES','San MatÃ­as No. 50, Col. San Javier Guanajuato.','4737341167','https://www.google.com/maps?ll=21.027421,-101.259918&z=19&t=h&hl=es&gl=MX&mapclient=embed&cid=3449872991165791618',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(2,1,35863,'RESTAURANTE EDELMIRA','Allende No. 7 Col. Centro Guanajuato.','4737341370','https://www.google.com/maps?sca_esv=e63747d83a281166&output=search&q=Restaurante+Edelmira+Allende+No.+7+Col.+Centro+Guanajuato.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(3,1,35863,'CASA KLOSTER','Alonso No. 32 Col. Centro, Guanajuato.','4737320633','https://www.google.com/maps?sca_esv=e63747d83a281166&output=search&q=Casa+Kloster+Alonso+No.+32+Col.+Centro,+Guanajuato.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(4,1,35863,'TROMPO DE GUANAJUATO','Allende No. 7 Col. Centro Guanajuato.','4737341370','https://www.google.com/maps/place/El+Trompo+De+San+Javier/@21.016062,-101.254906,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b73f80808f2fb:0x6bbb90c752f2d9b5!8m2!3d21.016062!4d-101.2523311!16s%2Fg%2F11c48_6q2y?entry=ttu&g_ep=EgoyMDI1MDgyNC4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(5,1,501423,'DELICAMITSU','Campanero #5 Col. Guanajuato Centro.','4731166492','https://www.google.com/maps/place/Delica+Mitsu/@21.0135512,-101.2539338,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b73f88eabc629:0x1bf2d698cb610808!8m2!3d21.0135512!4d-101.2513589!16s%2Fg%2F11c44prg2_?entry=ttu&g_ep=EgoyMDI1MDgyNC4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(6,1,41195,'RESTAURANTE BAR \"MARISCOS EL AMIGO\"','Calle AlhÃ³ndiga No 42, Zona Centro Guanajuato.','4737326244','https://www.google.com/maps/place/Mariscos+el+Amigo+de+Silao/@21.0246952,-101.2614734,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b740c65e4bae3:0xa43e1126489582e1!8m2!3d21.0246952!4d-101.2588985!16s%2Fg%2F1tdqtvyd?entry=ttu&g_ep=EgoyMDI1MDgyNC4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(7,1,500028,'RESTAURANTE CARNES EN SU JUGO DEL CHARRO','Carretera de Cuota Guanajuato-Silao No. 450 Local 1 Yerbabuena.','4737333521','https://www.google.com/maps?sca_esv=e63747d83a281166&output=search&q=Restaurante+Carnes+en+su+jugo+del+Charro+Carretera+de+Cuota+Guanajuato-Silao+No.+450+Local+1+Yerbabuena.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(8,1,43067,'RESTAURANTE LAS CARRETAS','Carretera Guanajuato- Dolores Hidalgo km 1 Colonia San Javier.','4736883699','https://www.google.com/maps?ll=21.030928,-101.257839&z=20&t=h&hl=es&gl=MX&mapclient=embed&cid=10787891241875318719',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(9,1,43067,'RESTAURANTE EL FURGON','Carretera Guanajuato- Dolores Hidalgo km 1 Colonia San Javier.','4736883699','https://www.google.com/maps?ll=21.030855,-101.258052&z=9&t=h&hl=es&gl=MX&mapclient=embed&cid=2958622906032632683',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(10,1,43067,'RESTAURANTE TRAS LOMITA','Carretera Federal Guanajuato- Dolores Hidalgo km. 5 + 100 Santa Rosa de Lima.','4736883699','https://www.google.com/maps/place/Tras+Lomita+Restaurante/@21.0703782,-101.210143,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b73d587c1cc6d:0xbeecc73eae3ae3e5!8m2!3d21.0703782!4d-101.2075681!16s%2Fg%2F11kms53ltf?entry=ttu&g_ep=EgoyMDI1MDgyNC4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(11,1,502092,'CORAZON A CORAZON','Av.Juan Silveti #223 Col. Santa Fe. Guanajuato.','4736902273','https://www.google.com/maps/place/De+Coraz%C3%B3n+a+Coraz%C3%B3n/@21.011509,-101.278442,132m/data=!3m1!1e3!4m6!3m5!1s0x842b756fcf67cfc1:0x474ec4aec954fb55!8m2!3d21.0115089!4d-101.2784423!16s%2Fg%2F11h26w8trv?hl=es&entry=ttu&g_ep=EgoyMDI1MDgyNC4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(12,1,43004,'PORTOFINO','Rafael Corrales Ayala 15 A. Marfil, Guanajuato.','4737333579','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Portofino+Rafael+Corrales+Ayala+15++Marfil,+Guanajuato.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(13,1,35989,'PULPO LOCO','Carretera Federal Guanajuato - Silao Km 5.2 Col. Marfil, Guanajuato.','4737330091','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Pulpo+Loco+Carretera+Federal+Guanajuato+-+Silao+Km+5.2+Col.+Marfil,+Guanajuato.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(14,1,500272,'CANASTILLO DE FLORES','Plaza de la Paz #32 Colonia Centro Guanajuato.','4737327198','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Canastillo+de+Flores&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(15,1,46900,'DON OLE','Subida principal #43, Zona Centro, Guanajuato.','4737328886','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Don+OlÃ©+Calle+SopeÃ±a+%2314+B+Guanajuato,+Centro.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(16,1,41790,'RESRAURANT-BAR LA OREJA DE VAN GOGH','Calle SopeÃ±a #14 B Guanajuato, Centro.','4731027240','https://www.google.com/maps/place/La+Oreja+De+Van+Gogh+Restaurant/@21.0175977,-101.2588271,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b7408917b21dd:0xca9db5ef428dfe22!8m2!3d21.0175977!4d-101.2562522!16s%2Fg%2F11c1xg9ccd?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(17,1,35913,'THEO','Calle San Fernando # 24, 26 y 28 Guanajuato Centro.','4737320908','https://www.google.com/maps?ll=21.015923,-101.253168&z=16&t=h&hl=es&gl=MX&mapclient=embed&cid=10464759375836355040',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(18,1,35913,'VAN GOGH','JardÃ­n UniÃ³n #2 Colonia Centro. Guanajuato.','4737320908','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Van+Gogh+Calle+JardÃ­n+UniÃ³n+%234+Bajos,+Guanajuato+Centro.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(19,1,40347,'VIRGEN DE LA CUEVA','Calle JardÃ­n UniÃ³n #4 Bajos, Guanajuato Centro.','4731166764','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Virgen+de+la+Cueva&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(20,1,31229,'CASA VALADEZ','Ex Hacienda de San Antonio #37 Colonia Noria Alta, Guanajuato.','4737320311','https://google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Casa+Valadez&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(21,1,49281,'CASA OFELIA','JardÃ­n de la UniÃ³n, Esquina Calle SopeÃ±a No.3 Guanajuato Centro.','4737312639','https://www.google.com/maps/place/Casa+Ofelia+Restaurante-Bar/@21.016292,-101.253021,1077642m/data=!3m1!1e3!4m6!3m5!1s0x842b7407fcac9793:0xc578807e6552e089!8m2!3d21.0162923!4d-101.2530208!16s%2Fg%2F1hc8npkq4?hl=es&entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(22,1,35802,'LA TASCA DE LA PAZ','Calle Truco #11 B Zona Centro, Guanajuato.','4737342225','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=La+Tasca+de+la+Paz&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(23,1,40345,'EL POTRO LOCO BURRITOS Y TACOS','Plaza de la Paz No. 28, Zona Centro, Guanajuato.','4737323579','https://www.google.com/maps/place/El+potro+loco+Gto/@21.0137077,-101.2509273,66m/data=!3m1!1e3!4m6!3m5!1s0x842b73f88a52ec29:0xd065074784eb5c59!8m2!3d21.0137899!4d-101.2509655!16s%2Fg%2F11cjjbn_3c?hl=es&entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(24,1,44741,'PIZZA BAEZ CENTRO','Calle Manuel Doblado #45 Zona Centro, Guanajuato.','4737325969','https://www.google.com/maps?ll=21.018198,-101.256166&z=16&t=h&hl=es&gl=MX&mapclient=embed&cid=9625340772407997260',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(25,1,37905,'LA SANTURRONA','CallejÃ³n de Cantarios No. 30 Col. Centro. Guanajuato.','4737339733','https://www.google.com/maps?num=12&sca_esv=68354a3c7c70afc3&biw=1920&bih=945&output=search&q=La+Santurrona&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(26,1,40196,'PIZZA BAEZ EUQUERIO','CallejÃ³n Potrero #2 Bajos, Zona Centro, Guanajuato.','4737331690','https://google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Pizza+Baez+Euquerio&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(27,1,502295,'DOMINOÂ´S PIZZART','Carretera J. Rosas km 6.6 Col. Arroyo Verde, Guanajuato.','4737322256','https://www.google.com/maps/place/Domino\'s+Guanajuato/@21.0127191,-101.254235,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x85cd89ec5fa6f79b:0x74bc35ca3abc4f75!8m2!3d21.0127191!4d-101.2516601!16s%2Fg%2F11f3vrccfl?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(28,1,44656,'RESTAURANTE BAR LUNA','Sostenes Rocha No. 35, Zona Centro Guanajuato.','4737341864','https://www.google.com/maps?ll=21.016079,-101.252922&z=6&t=h&hl=es&gl=MX&mapclient=embed&cid=7050874983700914461',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(29,1,503078,'LA FONDA DEL MINERO (SOCAVÃ“N)','JardÃ­n de la UniÃ³n #10 Zona centro, Guanajuato.','4731511992','https://www.google.com/maps/place/La+fonda+del+minero/@21.0233963,-101.2615386,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b7500022a3b93:0xc0d6e6d943dab249!8m2!3d21.0233963!4d-101.2589637!16s%2Fg%2F11wjlh0qpp?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(30,1,46952,'MARISCOS LA JAULA','Av. AlhÃ³ndiga #41 A Col. Guanajuato Centro.','4737340180','https://www.google.com/maps/place/Mariscos+La+jaula/@21.0093773,-101.2557249,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b74009828004d:0x18a552b35d278db3!8m2!3d21.0093773!4d-101.25315!16s%2Fg%2F11cmq_lpj0?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(31,1,47927,'MUNCHIK','Boulevard Guanajuato No. 39 A, Guanajuato.','4731398627','https://www.google.com/maps/place/Munchick/@21.0168,-101.2575888,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b750d52ec87f3:0x48fa6f8c174d8cfa!8m2!3d21.0168!4d-101.2550139!16s%2Fg%2F11pv2nh5fn?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(32,1,46511,'TERRAZA TRATTORIA','Calle Alonso #61 Guanajuato Centro.','4736880813','https://www.google.com/maps/place/Terraza+Trattoria/@20.9974776,-101.2888767,1053m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b769cb2034969:0x5d62fe247fdffa38!8m2!3d20.9974776!4d-101.2863018!16s%2Fg%2F11cmpktztc?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3DD',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(33,1,46511,'TRATTORIA CENTRO','Jalpa #5 Col. Marfil, Guanajuato.','4737321261','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Trattoria+Centro&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(34,1,35893,'CAFE CONQUISTADOR','JardÃ­n UniÃ³n 1 Altos Col. Centro, Guanajuato.','4731028692','https://www.google.com/maps?ll=21.018089,-101.254852&z=16&t=h&hl=es&gl=MX&mapclient=embed&cid=17445662967611959239',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(35,1,43300,'ESCAROLA RESTAURANTE BAR Y CAFÃ‰\r\n','Calle AlhÃ³ndiga #100 Col. San Javier, Guanajuato.','4737320632','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Escarola+Restaurante+Bar+y+CafÃ©&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(36,1,45798,'RESTAURANTE LA HACIENDA (HOTEL GRAN PLAZA)','Positos #35 Guanajuato, Centro.','4737331990','https://www.google.com/maps?ll=20.987148,-101.284508&z=16&t=h&hl=es&gl=MX&mapclient=embed&cid=9404588992086979397',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(37,1,500214,'MARISCOS LA CURVA','Positos No. 34, 36 y 38A, Zona Centro, Guanajuato.','4736907516','https://www.google.com/maps?ll=21.03007,-101.256253&z=3&t=h&hl=es&gl=MX&mapclient=embed&cid=8818894250808603613',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(38,1,502134,'REST. POSADA SANTA FE','Carretera Guanajuato- Juventino Rosas km 6 Col. BurÃ³cratas, Guanajuato.','4737320084','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Rest.+Posada+Santa+Fe+JardÃ­n+de+la+UniÃ³n+12,+14+y+16+Colonia+centro+Guanajuato.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(39,1,503110,'SUSHI TAI','Carretera Guanajuato- Dolores Hidalgo #7 Col. Valenciana, Guanajuato.','4776272807 / 4776272806','https://www.google.com/maps/place/Sushitai+-+Ala%C3%8Fa/@20.9735444,-101.2808766,1053m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b77542dc98bbf:0x2b5ce8427212ec61!8m2!3d20.9735394!4d-101.2783017!16s%2Fg%2F11k3mvwm39?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(40,1,35911,'EL TAPATIO','JardÃ­n de la UniÃ³n 12, 14 y 16 Colonia centro Guanajuato.','4737323291','https://www.google.com/maps?ll=21.017015,-101.253633&z=5&t=h&hl=es&gl=MX&mapclient=embed&cid=16994336090293336572',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(41,1,40350,'LOS OLIVOS','Blvd. Euquerio Guerrero 139 Loc. RE-02 Col. Barrio Yerbabuena, Guanajuato.','4737322731','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Los+Olivos+Plaza+de+San+Fernando+%2312+%2314+Zona+Centro+Guanajuato.&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(42,1,40350,'CERRO DE LAS RANAS','Lascurain de Retana #20 Zona Centro, Guanajuato.','4737327180','https://www.google.com/maps?ll=21.017876,-101.256238&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=8811081271472492866',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(43,1,42111,'LOS PIANTAOS','Plaza de San Fernando #12 #14 Zona Centro Guanajuato.','4737393072','https://www.google.com/maps?ll=21.013569,-101.250948&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=11030426282894985906',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(44,1,49405,'SERVINTE','Plaza de San Fernando #45, 46 y 47 Zona Centro Guanajuato.',NULL,NULL,NULL,NULL,'2026-04-17 14:34:15',NULL,1),
(45,1,500294,'RESTAURANTE PASEO DE LA PRESA','Manuel Doblado 39-41 Guanajuato, Centro.','4737384672','https://www.google.com/maps?ll=21.003934,-101.244121&z=13&t=h&hl=es&gl=MX&mapclient=embed&cid=4245760557889025257',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(46,1,500294,'CORAZON MEXICANO CENTRO','Paseo de la Presa No. 142 Guanajuato.','4775353171','https://www.google.com/maps?ll=21.016377,-101.256613&z=9&t=h&hl=es&gl=MX&mapclient=embed&cid=593101499763183971',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(47,1,500294,'PICAÃ‘AS Y ESPADAS','CallejÃ³n del Patrocinio No. 15 Zona Centro Guanajuato.','4737372109','https://www.google.com/maps?ll=21.031681,-101.258583&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=708875651762097822',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(48,1,503194,'SANTO CAFE','Carretera Pan. Carrizo - San Javier S/N. Guanajuato.','4731222320','https://www.google.com/maps?sca_esv=68354a3c7c70afc3&output=search&q=Santo+CafÃ©&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(49,1,35804,'ARLES','Calle Campanero #4 Guanajuato, Centro.','4737323504','https://www.google.com/maps/place/Caf%C3%A9+Arl%C3%A9s/@21.017926,-101.255897,4209m/data=!3m1!1e3!4m6!3m5!1s0x842b755677ca77d5:0xefeaf1a8ede5227!8m2!3d21.0179264!4d-101.2558967!16s%2Fg%2F11vym10r2s?hl=es&entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(50,1,503083,'XOLAZUL','San Fernando esquina CallejÃ³n de Cantaritos #53, Guanajuato.','4731219480','https://www.google.com/maps?ll=21.015116,-101.253984&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=8159181866593687733',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(51,1,500252,'OCAZZO','Calle Constancia #10 B. Guanajuato Centro.','4737328097','https://www.google.com/maps?ll=21.018122,-101.255086&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=10295007334124149129',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(52,1,36859,'PETERÂ´S PIZZA EUQUERIO','Calle Positos Esquina Juan Valle #30, Guanajuato Centro.','4731146228','https://google.com/maps?sca_esv=49d605c33f00d0c3&output=search&q=Peter%27s+Pizza+Euquerio&source=lnms&entry=mc&ved=1t:200715&ictx=111',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(53,1,35952,'PETERÂ´S PIZZA CENTRO','Carr. Estatal Juventino Rosas km 5.5; Col. BurÃ³cratas, Guanajuato.','4737329636','https://www.google.com/maps/place/Peters+Pizza/@21.016762,-101.252408,134705m/data=!3m1!1e3!4m6!3m5!1s0x842b7407fff75b3d:0x8ccc874c6158c682!8m2!3d21.016762!4d-101.252408!16s%2Fg%2F1tfzxtj4?hl=es&entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(54,1,33814,'PORTICO (ATRIO)','Calle Ayuntamiento #19 Guanajuato, Centro.','4737311213','https://www.google.com/maps?ll=21.016105,-101.25217&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=5844247129139852833',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(55,1,503816,'TORTAS EL CHAVO DE ALONSO','Calle Agora del Baratillo, Guanajuato Centro.','4731076396','https://www.google.com/maps/place/Tortas+el+chavo+de+Alonso/@21.0159577,-101.2567792,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b75520293e6bb:0x3bd25146265ce1b7!8m2!3d21.0159577!4d-101.2542043!16s%2Fg%2F11vzyj007w?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(56,1,39053,'REDFISH','Calle Alonso No 14 Guanajuato, Centro.','4731027096','https://www.google.com/maps?ll=21.01651,-101.252777&z=15&t=h&hl=es&gl=MX&mapclient=embed&cid=8153176712049628057',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(57,1,501509,'DOS CHINGONES','Calle Truco No 18 Guanajuato Centro.','4731529331','https://www.google.com/maps/place/Dos+Chingones+Presa/@21.001612,-101.240801,269437m/data=!3m1!1e3!4m6!3m5!1s0x842b7798823e045f:0xa4ebaeabf614a028!8m2!3d21.0016124!4d-101.2408011!16s%2Fg%2F11jxz71680?hl=es&entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(58,1,502063,'OPERADORA TURISTICA PASEO DE LA PRESA','Presa de la Olla No. 133 Col. Paseo de la Presa, Guanajuato.',NULL,NULL,NULL,NULL,'2026-04-17 14:34:15',NULL,1),
(59,1,503806,'LA CAPILLA (EX HACIENDA SAN XAVIER)',NULL,'4731021500','https://www.google.com/maps?ll=20.919925,-101.221088&z=10&t=h&hl=es&gl=MX&mapclient=embed&cid=10706922799433101583',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(60,1,502063,'LAS TIAS',NULL,NULL,NULL,NULL,NULL,'2026-03-15 14:50:34',NULL,1),
(61,1,503000,'KALDI',NULL,'4646502633','https://www.google.com/maps/place/Caf%C3%A9+Kaldi/@21.015637,-101.2541965,1052m/data=!3m2!1e3!4b1!4m6!3m5!1s0x842b733ad6160ad9:0x20cfe2b7a2b2a61a!8m2!3d21.015637!4d-101.2516216!16s%2Fg%2F11vqmcx9cf?entry=ttu&g_ep=EgoyMDI1MDgxOS4wIKXMDSoASAFQAw%3D%3D',NULL,NULL,'2026-04-17 14:43:44',NULL,1),
(62,2,35863,'CASA VIRREYES','Calle Ponciano Aguilar No. 49 y 51 Col. Centro Guanajuato C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(63,2,35863,'ABADIA TRADICIONAL','San MatÃ­as No. 50, Col. San Javier Guanajuato C.P. 36020',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(64,2,35863,'REAL DE LAS LEYENDAS','AlhÃ³ndiga No. 10-B Col. Centro, Guanajuato C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(65,2,503078,'EL SOCAVON','Av. AlhÃ³ndiga #41 A Col. Guanajuato Centro. C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(66,2,500394,'EX HACIENDA SAN XAVIER','Calle Plaza Aldama No. Ext. 92 Fracc. San Javier. Guanajuato, Gto. C.P. 36020',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(67,2,45798,'GRAN PLAZA','Carretera Guanajuato- Juventino Rosas km 6 Col. BurÃ³cratas, Guanajuato, Gto. C.P. 36250',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(68,2,38117,'HOTEL LA PAZ','CallejÃ³n del Estudiante #1 Col. Centro Guanajuato C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(69,2,38117,'SANTA REGINA','Alonso #26 Col. Centro C.P. 36000 Guanajuato, Guanajuato',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(70,2,41775,'HOTEL PLATA CONDESA',' CallejÃ³n de la Condesa #2 Col. Centro Guanajuato C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(71,2,502134,'POSADA SANTA FE','JardÃ­n de la UniÃ³n #12,14 y 16, Col. Centro, Guanajuato, Guanajuato CP.36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(72,2,502063,'PASEO DE LA PRESA','Blvd. Carretera PanorÃ¡mica Tramo PÃ­pila Issste S/N, Guanajuato, Gto. C.P. 36080',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(73,2,45609,'HOLIDAY INN','Boulevard Euquerio Guerrero #120 Col. BurÃ³cratas Guanajuato C.P. 36250',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(74,2,46783,'VILLA MARIA CRISTINA','Paseo de la presa de la olla 80 A Zona Centro, Guanajuato C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(75,2,46822,'MANSION DEL CANTADOR','JardÃ­n El Cantador #19 Guanajuato centro C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(76,2,43752,'HOSTERIA DEL FRAYLE','Calle SopeÃ±a #3 Guanajuato, Centro C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(77,2,500294,'CORAZON MEXICANO BOUTIQUE','Carretera PanorÃ¡mica Carrizo San Javier S/N C.P 36020',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(78,2,42108,'MESON DEL ROSARIO','Av. JuÃ¡rez #31 Guanajuato Centro. C.P. 36000\r\n',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(79,2,35863,'EDELMIRA HOTEL BOUTIQUE','Allende No. 7 Col. Centro, Guanajuato C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(80,2,35863,'CASA KLOSTER HOTEL BOUTIQUE','Alonso No. 32 Col. Centro, Guanajuato C.P. 36000\r\n',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(81,2,500294,'HOTEL SUITE CM','Carretera PanorÃ¡mica Carrizo San Javier S/N C.P 36020\r\n',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(82,2,500294,'HOTEL CORAZÃ“N MEXICANO CENTRO','CallejÃ³n del Rosarito No. 7 Col. Centro Guanajuato C.P. 36000\r\n',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(83,2,43752,'HOSTELERIA DEL FRAYLE','Calle SopeÃ±a #3 Guanajuato, Centro C.P. 36000',NULL,NULL,NULL,NULL,'2026-04-17 13:32:37',NULL,1),
(84,2,32,'REAL GUANAJUATO','Positos #39, Guanajuato Centro, C.P. 36000\r\n',NULL,NULL,NULL,NULL,'2026-05-08 18:54:24',1,1),
(85,3,999999,'SECTURIFIC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(86,4,888888,'CLIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(87,3,NULL,'SIN ESTABLECIMIENTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),
(88,1,17,'TACOS ROY',NULL,NULL,NULL,NULL,1,NULL,1,1);


UPDATE establecimiento e
JOIN establecimiento_restore_stage s
  ON s.id_establecimiento = e.id_establecimiento
SET
  e.id_tipo = s.id_tipo,
  e.no_proveedor = s.no_proveedor,
  e.dsc_establecimiento = s.dsc_establecimiento,
  e.direccion = s.direccion,
  e.telefono = s.telefono,
  e.ubicacion = s.ubicacion,
  e.fec_reg = s.fec_reg,
  e.usu_reg = s.usu_reg,
  e.fec_act = s.fec_act,
  e.usu_act = s.usu_act,
  e.visible = s.visible;

INSERT INTO establecimiento (
  id_establecimiento,id_tipo,
o_proveedor,dsc_establecimiento,direccion,	elefono,ubicacion,ec_reg,usu_reg,ec_act,usu_act,isible
)
SELECT
  s.id_establecimiento,s.id_tipo,s.
o_proveedor,s.dsc_establecimiento,s.direccion,s.	elefono,s.ubicacion,s.ec_reg,s.usu_reg,s.ec_act,s.usu_act,s.isible
FROM establecimiento_restore_stage s
LEFT JOIN establecimiento e
  ON e.id_establecimiento = s.id_establecimiento
WHERE e.id_establecimiento IS NULL;

DROP TABLE establecimiento_restore_stage;

SELECT COUNT(*) AS total_final, MIN(id_establecimiento) AS min_id, MAX(id_establecimiento) AS max_id FROM establecimiento;
SELECT id_establecimiento, no_proveedor, dsc_establecimiento FROM establecimiento WHERE id_establecimiento IN (1,2,3,4,62,63,64,79,80,89,90,91) ORDER BY id_establecimiento;