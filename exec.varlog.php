<?php
$GLOBALS["VERBOSE"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}


if($argv[1]=="--squid"){var_log_squid();die();}


//$A=unserialize(base64_decode("YToyMDU6e3M6Mjc6IjJPWlRBWi1INU9HRzItWEQyRFJBLVlNN0lOWSI7YjoxO3M6Mjc6IldVRkhUNi1aT1VYTDktU00xWFFSLU9RTUVVQSI7YjoxO3M6Mjc6IktBRVFFMi1ZRUROUzEtSU5TSU9YLUJYNVdLNCI7YjoxO3M6Mjc6IlBQUEFUSi0ySDlWUkEtQTFMVktGLTlKTVZNMiI7YjoxO3M6Mjc6Ik9PVUk5TS1DN0RFUEEtSEdDRzFELUw2NEhKSSI7YjoxO3M6Mjc6IkNFUExGQi1GTjVMMTMtWFFVM1NBLVVWTEpLSiI7YjoxO3M6Mjc6Ik41NVlIUi1MQUYwQ1QtM0lWSlY5LTM5WllJQyI7YjoxO3M6Mjc6IkIxSklPSy1HWFNMUVMtRUFaMVhHLUxZOFNFUyI7YjoxO3M6Mjc6Ik9IVDZQVS00UElFUFgtOUZDTkJJLTlTREVWRiI7YjoxO3M6Mjc6Ikk5WEE2RC0yTk5BRjItNk1VQ0pPLTU5UFA4TCI7YjoxO3M6Mjc6IkU0TENXWi1WQlRKUVotTkFLWE5TLVNRT1JQMiI7YjoxO3M6Mjc6IlBaWE5LVS1VMkREQ1ktTUZPM0ZVLVFSRzhHTiI7YjoxO3M6Mjc6IlFETDVPRS0yMEhVOVctSlkyV05GLVpFR0JGRiI7YjoxO3M6Mjc6IlRBUk9EQS1TSkdRM0stR1lNRjBULThaSTlPVyI7YjoxO3M6Mjc6IlFBMUJBSy1YRFNWWFItWVUyRDdCLUNYNDdDWSI7YjoxO3M6Mjc6IkUwWlRZTi1PNlJSRkEtVDlNVTlaLUdaOFFBRCI7YjoxO3M6Mjc6IjhUM09OMS0xQTU5Qk0tR1dZRThULVRGR0ZXUyI7YjoxO3M6Mjc6IkZQWFZEMy1ORkMwR1YtVlFJVko5LVlJSFVMSyI7YjoxO3M6Mjc6IkJWUlRTSC1CSVdMVEUtQlJCMFhDLVJIV0RGQiI7YjoxO3M6Mjc6IkwxVlpJMC1VSlRTUU8tT1pFTkFHLVRVUVdPQiI7YjoxO3M6Mjc6IkhaVVFQRS1LV0VPRFctUUlDRE5SLUREV0pYNiI7YjoxO3M6Mjc6IklRRjUyRS1aTUZVOEUtMFdHQzdLLURRMktBQSI7YjoxO3M6Mjc6IjhEU1ZQWi03WTg2RUUtVUgxQTlULTVKSUhLQyI7YjoxO3M6Mjc6IlFRRElLUC1EOE5VSkYtOURGRFpaLUZJUk9QTyI7YjoxO3M6Mjc6IlpaUjg4OC1VRFdHVUgtRDhGTkxaLVNIT0ZHMyI7YjoxO3M6Mjc6IklEUDdTTS1INkJTUEotRlVSUUNMLUhBRjlNNCI7YjoxO3M6Mjc6IlJNR1VCVi1ESlJCTlMtWE1FVDlQLU5ISUlDUyI7YjoxO3M6Mjc6IlZOUFlOVS1RN1ZFWkItME5VOFBULUNaWEJEUCI7YjoxO3M6Mjc6IlBGREk2Uy1PQUVOV1UtNTFaNDFOLTI1TEk5WSI7YjoxO3M6Mjc6IjlLRUlCSC1FUkxIWUQtRkdDWk9VLVRVWEdWWSI7YjoxO3M6Mjc6IjNEOE5INy1IRzNMREktQkdSQU0yLTlGNUw1TiI7YjoxO3M6Mjc6Ikg4SVNVUS04T0dZTjktSUZPVU5OLVpOTVVPVSI7YjoxO3M6Mjc6IlpIVUhIUi00S05MNVotM0lZQVAwLVNCOVJENSI7YjoxO3M6Mjc6IlNWNVNQUi1JOU5VVFAtTkFMRE5JLUJOMkpVTiI7YjoxO3M6Mjc6IktOVU5HNC1OVktXQ04tWVI0WEk5LVZGRjYwRiI7YjoxO3M6Mjc6IllMTEtERi1KT0VYSTAtSUFTQTVaLVk1Q1BFRyI7YjoxO3M6Mjc6IklVWkhCRS1FUUZPUkEtT0xaUUI2LTFCQjNINCI7YjoxO3M6Mjc6Ik4ySzBaTi02SzhLSjAtOUpCRkZWLUY5VVNEVyI7YjoxO3M6Mjc6IlhGOFpROC1PRlpXUVgtV0xRQUZYLVNUVVNXTiI7YjoxO3M6Mjc6IkpTUlo1Vi1FRVBYU1gtMlNIRUhHLU5DOUlITyI7YjoxO3M6Mjc6IkVaUktQVS1FT1FVS0gtU1BTWEIxLVdTS1BQQSI7YjoxO3M6Mjc6IkZQRkUyQS02OUtGNkwtUEdOTFowLUlTMFlJQyI7YjoxO3M6Mjc6IjdCTU5UWC1FRUpWQUwtWUIzM0pLLUtOQlpGMSI7YjoxO3M6Mjc6IkJPOU5JSy1QUUJCNFotRkpLTVE0LVhTSzhERCI7YjoxO3M6Mjc6Ilg2UzZMRC01U1JYWlctUUhBNVFELVNCSDhBWSI7YjoxO3M6Mjc6IkdBVEE0Ui1SWDBJSkItRDFQV0ZFLTJUUDNVUCI7YjoxO3M6Mjc6IkJLMkdaTy1FQ0tXQU8tSFhBWU9RLTZYWUk3NiI7YjoxO3M6Mjc6IlZOVzBJVy1CTUs5VE4tWTkzR0FKLVdQUk1NUyI7YjoxO3M6Mjc6IkxaR0dPMC1COURITVItTTBQUDNCLU1ZRVlIVCI7YjoxO3M6Mjc6IjJVTzVMVC0yNkhXS0stNDVMR0RILU9BVlVWUyI7YjoxO3M6Mjc6Ik81SlAxVS1BRzlNUTQtWjlWRjVCLU1GTEFDUiI7YjoxO3M6Mjc6IjZYRFlDVy1LNFBISUEtNEYwVjVCLThXTVpRUCI7YjoxO3M6Mjc6Ik5UMVFOTC03WlVFTDQtOFBYSVdXLU1EWFRFUiI7YjoxO3M6Mjc6IlFENzhCVS01UklaU04tMTVHTEVCLVZIQVk4SCI7YjoxO3M6Mjc6IlRVSk1LRS1ZQ0hFRUwtRUZMTk1LLVhSVExQVyI7YjoxO3M6Mjc6IldIU0sxTC00VlhSV0YtTElQQ1dNLUFQUlhGUSI7YjoxO3M6Mjc6IkZBM1hQMi1USzgyRFItUFpXWUlELTgwVlA5TSI7YjoxO3M6Mjc6IklDQjZXSC1EU1hNWVEtTzJIMUVaLTVKSFpTViI7YjoxO3M6Mjc6IkhYRDlGRS01WFVBRFItQzROSFRJLUZXTE5aNSI7YjoxO3M6Mjc6IklXRk5FRi1OQUcyUDUtVU0yWFdTLVBFN1RTSyI7YjoxO3M6Mjc6IjE3TFpaQi00NDVYSFMtUElMVlhTLVQ3Nk9CRCI7YjoxO3M6Mjc6Iko2V1JUVy1NWVJOOEgtWFBCVzZNLUdVQ09ZTiI7YjoxO3M6Mjc6IlZCRUtXRi1OM1NEWUEtTjJZMU45LUxVTElOSCI7YjoxO3M6Mjc6IkJQS0RPSC1PRktKOU4tWUpRSE9ZLU1XRVdQWiI7YjoxO3M6Mjc6Ik9CR1o5US1SWUZCUlgtV1FQWEVQLVowRkJGUSI7YjoxO3M6Mjc6IlVZOU9RRS1CTTA0Q0wtVUNYTEhQLTZYQUQ0TiI7YjoxO3M6Mjc6IkFXR1lOUy1VQ1A4SEItQVcxRTJQLU9CR1hMWCI7YjoxO3M6Mjc6IksxQ0U0UC1JUTJGWTEtR1ZIVUJBLTFLTktGNCI7YjoxO3M6Mjc6IkFMU1lIUy1PRjk5TDktR1JWMlYwLVNIMkdaWSI7YjoxO3M6Mjc6IkFZSkM5TC1DT0lKRlItUElFWUpJLVcxVUxFUiI7YjoxO3M6Mjc6IkhXV0hKMS1WREZIUkYtTTJRUU1XLTNUU1ZHVSI7YjoxO3M6Mjc6Ikw5UUhPVS01OTU1OUwtTVhaWVc0LVpLUEpaTiI7YjoxO3M6Mjc6IldXQVhaTS1KM0oyTVEtMlI3SENJLTBDWkpPSyI7YjoxO3M6Mjc6IkRHWU1DMC0wR0dVRkctN0RPQVE0LU1XQ0ZZQyI7YjoxO3M6Mjc6IjlZQUk0Ri1XSUIxMUYtUFBTRENDLVM4N0dEOCI7YjoxO3M6Mjc6IlRIVkZWTS1BS1JXTk4tQkpQME1WLVpQWUVTMCI7YjoxO3M6Mjc6Ik9YRUM1Vi01S1NNRFQtWUpUVTA5LUpRUTcyRiI7YjoxO3M6Mjc6IlU4VFFPVS1ZQjFZM0otVUxSVVNDLVhWSVpCWSI7YjoxO3M6Mjc6IkVRTk9UNC1KS0cySzctRFAzUE5ULTdMUU5STyI7YjoxO3M6Mjc6IlRUVVI2Si1LTzNENDQtSUlXQUE5LVVKS1RNTiI7YjoxO3M6Mjc6IkVBRERUSy1MN0paTlUtWUdKSk1aLThBUDFaTSI7YjoxO3M6Mjc6IlZBUlhSVi05SVNUUTUtQUc0S09CLVgyTkM0QyI7YjoxO3M6Mjc6IlRVREdRMC1XS09TMlMtTEVESFdRLVVOQVdDViI7YjoxO3M6Mjc6IlBKS0c0VC1aUERNSFctV0JDUk9FLUhJSzhWVSI7YjoxO3M6Mjc6IlkyRUhFSi1RQVFRUU0tV1MzQk0yLUxZSFBLSSI7YjoxO3M6Mjc6IkpMRlBaOC1WOUdETkwtVDZJMVg5LUZYWjNZMyI7YjoxO3M6Mjc6IlhHVkNVQy1FTjROSkctMFdaWFVJLVhGSEJCVyI7YjoxO3M6Mjc6IlBEUzFKVS1UTkNXUFAtUU5CQTVMLUI3QktKSSI7YjoxO3M6Mjc6IkFEUDZJRS1XTU5JWUMtTFZSTk5LLTVITVhXTyI7YjoxO3M6Mjc6IkJTT1lNUC1DVDBGWVctSTRNTDVaLVQyQlE4UiI7YjoxO3M6Mjc6IlNBUk9FWi1LSUJUUEctUU5TSTk0LUE4QUNFUiI7YjoxO3M6Mjc6IkhMT1lUUC04VVNVS0stWldZNk1LLUpKOE8yTiI7YjoxO3M6Mjc6Ik80VkFZVy01SlQwSEYtTExVWEhYLVlZQzdKQiI7YjoxO3M6Mjc6IkxQRjNQVy1PNVBCR1UtMFA1RUtOLVhTQ09PWSI7YjoxO3M6Mjc6IlI5UE40Qi1JWFc4RVMtOUJJT0xHLThURFA0UiI7YjoxO3M6Mjc6IjhGVU5DVi1YVlNPMVQtTDNWUUdELUFKUEtQUCI7YjoxO3M6Mjc6IlJFUTJOSi0wWlRXWkwtNk9FUTlJLUpUWUFQRSI7YjoxO3M6Mjc6IkhGU0lBQy1NVElZTFQtWUlGUUlELVVKS1hHMiI7YjoxO3M6Mjc6IkxZQjFXVC1WVzNMNVktUEJMUExJLUdIRUNaUyI7YjoxO3M6Mjc6Ik1JSkdCMS1MTlQyRDItUk0wS0RKLVRaRVFMNyI7YjoxO3M6Mjc6IkJUV0VPTi0zWTNESVctVEdDV1NYLUtPRUhSTCI7YjoxO3M6Mjc6IlRPUEJBSS1QQ0tUUEctN0pSSlVKLUVTQU5HMSI7YjoxO3M6Mjc6IkY0QTNCTS1KTkZPUVktVFhOTUtTLTFMVjlHTiI7YjoxO3M6Mjc6IlM2Uk1QRS1GQ0I2Tk0tUkVDSVoyLTJOSlBNRCI7YjoxO3M6Mjc6IldUSU5YUS1BMVFCS0QtSVVQQ0swLUhTS1dDVyI7YjoxO3M6Mjc6IjJYSUJHMy1TVEJRSjgtTzRVQllYLVlTQ1hHQyI7YjoxO3M6Mjc6Ik4wVVVUNC1DUFgyOUQtT0VNQ0pMLURJOFdPOCI7YjoxO3M6Mjc6IkM5SkJLVS1MSFpXQVQtV0Y0WVhWLTMzNlhNUSI7YjoxO3M6Mjc6IjAyTkFIUS1FWTA3V1YtSk9NVEhULUxKVk1FOSI7YjoxO3M6Mjc6IjBZMUZMMi1NSDZKRUMtSlpDR05ILVpST0VUTiI7YjoxO3M6Mjc6Ikk1Sk5YTi1UQk5ITkstR1JBVDk2LUpRTzZJMSI7YjoxO3M6Mjc6IjU4V0pNTi1US1NUQkotSkdMWkcwLUVPU0NDMyI7YjoxO3M6Mjc6IjhGT0RXTy1SRk5GWDEtSFNLRldYLUZVS05QUCI7YjoxO3M6Mjc6Ik5aQ1pFWi1PRlUzWTItVzYwN0hELVVaUFlGVCI7YjoxO3M6Mjc6IlRBQ0dPSS01VEJQM1ktM1pCUk9PLUdCT1lTUCI7YjoxO3M6Mjc6IlEyUVVSQi1KMERBWUEtMTNWU1lRLTBKMEpVWSI7YjoxO3M6Mjc6IkhYUkNBWS00Q0owVk0tQlJJWVpTLVVWR04wRSI7YjoxO3M6Mjc6IkE4T1ZHNi1QWVNNTEEtTEJSWFZNLUpISTVJOCI7YjoxO3M6Mjc6IkFZOThaRi1WQ1ZEVU8tVjdDREVPLVRTTVZRNyI7YjoxO3M6Mjc6IlJZSkgzWC1IUUMwTU4tWkkyNjBKLUNTSUY2TSI7YjoxO3M6Mjc6IkUxRkNOWC1OV0NSMUQtTFlVNVRGLU8xREJBWCI7YjoxO3M6Mjc6IjRBTVNRVC1YQlowOEYtWU45WkVELTM4UkhIUSI7YjoxO3M6Mjc6Ik9URUZCSy1SSlBLN0wtOEVPRFg0LTlCTVZaMSI7YjoxO3M6Mjc6IkQxOUhYWC1SUEFaWkEtV1dVRUFXLVBTTjhBVyI7YjoxO3M6Mjc6Ik9LT0xLQy05Q0hKTkgtTE1BSEtFLVVISlpTRiI7YjoxO3M6Mjc6IlZXWlpWQy1VS1RaRlAtRU83MFFFLVpHTThDNiI7YjoxO3M6Mjc6IjJBRk4xUy1IREkySU8tSjRGWVhNLVVaMkdMRyI7YjoxO3M6Mjc6IllYRUJFSi1OMEhKVUMtWkJPWllRLVNEOUFXVCI7YjoxO3M6Mjc6IlJTVFlBRS1SUEVJU1MtVE9aQlFPLVBaUFE2QiI7YjoxO3M6Mjc6IjhCQVhGUi1LSVNJQUYtNVFVN0RYLTZPUlBYNCI7YjoxO3M6Mjc6IllWT1hLWi1UQ0EwWFItV0NWUk5YLVVFSVdYWSI7YjoxO3M6Mjc6IjBPQkxSVC1PTExBWkotQTlPMldILVhMQklNTyI7YjoxO3M6Mjc6IkROS1pSQS04MDNLSk4tVVU3WTRYLTg2RUdPNiI7YjoxO3M6Mjc6IkVSUUhZRi1HQkdDTkUtSk5QTEtLLVdWUENCVCI7YjoxO3M6Mjc6IlRHQ0ROSS1aVk1TRDktVzlKQlg0LTYwMEJQTCI7YjoxO3M6Mjc6Ik5FVUxRQi1YUk1VRlAtWEhMRE1JLVlJMFpaSSI7YjoxO3M6Mjc6Ikw1VjcxRy0wSEpUWlMtWUxNOU9OLUFaSExNOSI7YjoxO3M6Mjc6IjVFQ0dNRS1ZTktUQ0ktSkxYR0VILU5WWFJJTSI7YjoxO3M6Mjc6Ik5RQVVUTC1PREtHVTQtRVdQSVcyLUNHRU9YUSI7YjoxO3M6Mjc6IlFOSkRHOC1JRVRTSkotWFUwT1Q3LUJSNVlaRCI7YjoxO3M6Mjc6IlNFRkFIQy1ZUU9EQjAtUVFORUdPLVBTSEdFUyI7YjoxO3M6Mjc6IkxKUjhTUi1CSk1OQUMtWE04V0wzLUNZQ0dGRSI7YjoxO3M6Mjc6IkdPN1JORC1HSkk4RkYtQ1UzMUJQLUpETzdZSiI7YjoxO3M6Mjc6IkxGSkNLQy1USkdKMjAtTDlOSTI3LU9ONFVCSyI7YjoxO3M6Mjc6IkZCV0VCVS1CQ05NUEItQ1RQR0NULVNUUkdPViI7YjoxO3M6Mjc6IkFZVU5TUy1GVFpPQjUtWFJGSzQxLVBGR0ZIVCI7YjoxO3M6Mjc6Ik5MQU40Sy1KWEpEUU4tSU9XMk5ELVExN1JTUCI7YjoxO3M6Mjc6IldCVFhFTC1VTVhMUlotSENLRTdZLVhIMDVDRSI7YjoxO3M6Mjc6IjlIQ1NOMy1GU1M0UVctRElVNUFMLUEzT0dZUiI7YjoxO3M6Mjc6IkRBWEFSWi1UNEJaVVYtUEFNUkpJLVFKUDJPNyI7YjoxO3M6Mjc6IkpYMkNDWS1MNlZGWEEtQUJBS0hQLVpOMVBKQiI7YjoxO3M6Mjc6IktEWVVDTC1WUTJLWjgtM1FQWkhWLUFHV1NaMCI7YjoxO3M6Mjc6IlFYMkxOUi1RRVBHSFgtQ0lIUEdSLVg4WUNVSyI7YjoxO3M6Mjc6IlNLV1Q2TS1JR0FGNUEtTVpFWk1VLVZQU1dJTSI7YjoxO3M6Mjc6IklSQ1hUUC1TWDFCMUotRlhEQVk1LUZQUFJFTSI7YjoxO3M6Mjc6IjRQQ0hMRi1VQk4zSFgtTVNPSlMxLUM4WUNJRCI7YjoxO3M6Mjc6IlJITUhOSC1NRFdZWTMtOVBHSURGLVRLWlNTNyI7YjoxO3M6Mjc6IkxaTlVPOC1WM0lWMTgtUjRURk5GLVk4SlRNQSI7YjoxO3M6Mjc6IlBFUFZYNi1BVFozRUktUzgwNkNPLUpJRzJSSiI7YjoxO3M6Mjc6IkdTT0xLRy1aUFFLVVQtWlA3WllCLUhSUEtDMCI7YjoxO3M6Mjc6IlBTUkJRUS1ZRURZVUotSUdVWkZZLTBIR09VUCI7YjoxO3M6Mjc6IkVQRTlMWi1LSU1ENFotMkJOSFo1LUNEVVdXMSI7YjoxO3M6Mjc6IklaUEZTWS1FTVVDVE4tTzczS0JMLU9FSkFMTiI7YjoxO3M6Mjc6IlBSRUNCMC1CMFBXV0ctUllKUEJILTI1U1pPVSI7YjoxO3M6Mjc6IjVENlpTRy1HWlpTVlktR0ZVRFNNLU5VQjlTRSI7YjoxO3M6Mjc6IlNMVlRNTy1RQkFEMEwtVzJGWDNKLUdWSUZRTCI7YjoxO3M6Mjc6IktSQVlNUC1RUUJFMUMtQktIVVBZLUY3U0hVQSI7YjoxO3M6Mjc6IkxST0NHRC1SRVFLT08tNUozWkVaLU1WWUhEVSI7YjoxO3M6Mjc6IkNRQ0VTTi1STDJVQkotQ1I1U0VJLVdBUFVRWCI7YjoxO3M6Mjc6IjBDWldOSC1EQUJHV0ItRlFTUlAyLUs5UlNHSiI7YjoxO3M6Mjc6Ik1BRVFKRy1PRVBKODgtNUdQSVkyLUI5ODNaRiI7YjoxO3M6Mjc6IlRGVUZJNy1UUE9VNTAtNVNCSjNDLUJZRVlYSCI7YjoxO3M6Mjc6IkhIR1pZNi1DVElMNUstQk9OVkNILTk1WU5BVCI7YjoxO3M6Mjc6IjlFODJLRy04RTBaTUMtODNCV0RBLVY5TVhIQiI7YjoxO3M6Mjc6IkEyOVpCVy1USENDSDEtTFlQTVRJLU01RFBaUiI7YjoxO3M6Mjc6Ikg4SEdBWS1HVVQ3VlQtTE41NDlWLUpTTzVBSiI7YjoxO3M6Mjc6IjlPS0JGSC1KTkxRQ0wtTlVER1c5LUROV0s3VyI7YjoxO3M6Mjc6IllXR1VTVy1KR1hXQkItVDVNOTYxLUtPUlNINCI7YjoxO3M6Mjc6IkhVWUJINi1DUU1MQVMtTTdWTkpULUpVQ1QwMiI7YjoxO3M6Mjc6IkI0MElMUC1GSjVCVFctNUxIMThTLTgyVjFXTCI7YjoxO3M6Mjc6IjdXRjhQVC1URU9JT1ctSVc5NlVSLVI2R1BCNSI7YjoxO3M6Mjc6IkU1U0VEWi01TUNLN1AtS1JRT1hQLVBTQVZONiI7YjoxO3M6Mjc6Ilg2RU1JUy1TTThXSEwtSTJURE5RLVdNV1VPVCI7YjoxO3M6Mjc6Ik9UMzFDTi1BWUFISkYtRUcwUDZPLUJBOVNXWCI7YjoxO3M6Mjc6IloxQVdDQi0xUzZNQVYtQkVJS1ZHLUlETUNUUyI7YjoxO3M6Mjc6IkdNWlVaWC1XR1VJR1MtRlJPSDBLLTJRRzYwTyI7YjoxO3M6Mjc6IlVETVRORi1LUTNRVlgtRUJKNlFOLVhZNlA0RSI7YjoxO3M6Mjc6Ik5HUE1HNS00VFpaQ0UtRUlJMUVQLUZVNFlVQyI7YjoxO3M6Mjc6IjI4VUlaRC00SEdBTlctUFVTVUVRLUxRVFhSMCI7YjoxO3M6Mjc6IkJRV1lMVi1QSkJBNU0tSEdWTVZKLVZOTkdQTyI7YjoxO3M6Mjc6IkNaUklFTi01Vk1LRUQtNkxPTU01LVpPWUE4NiI7YjoxO3M6Mjc6IjNNVVhQMC1KR1pISVUtVEJMWENaLTdYSUJBVyI7YjoxO3M6Mjc6Ik02TElGRi1SWElGMVAtSFE2VVFCLTQ2STRDTSI7YjoxO3M6Mjc6IkNFWUpURS1ORkFWU1ctQUNQR1VJLVVTUlpTOSI7YjoxO3M6Mjc6IjdaT0pZQi1HOE5VNVAtRVlFNlVBLUFDRjJTRyI7YjoxO3M6Mjc6IlhSTEhSTS1PT01JUEYtVVQ4UDdLLVJFNkgzVCI7YjoxO3M6Mjc6IkpOS1NHQS1XQ0VJV00tR05JV1JNLVZMUDY4NSI7YjoxO3M6Mjc6IlgwV0dFVi1WSUNFS0UtTkRPQkpSLUxLUFFTTiI7YjoxO3M6Mjc6Ik5BQzFEOS1QQlJXTEctOTlZU1lCLVJVQ09XSSI7YjoxO3M6Mjc6IllXV1FGTi1RSlVMQlYtRlVUNlBQLUFYTlRFUyI7YjoxO3M6Mjc6IkhQVlVURC1XVTk3VEotTU1VUk1aLUZLOE1TVSI7YjoxO3M6Mjc6IkdQOFRSVC1aU1JENUUtVURBVVRMLVU4NlBZUiI7YjoxO3M6Mjc6IkdVWVdXRi0wRDE0WDktNUNCVVNaLVZHWVlOSSI7YjoxO3M6Mjc6Ik5MTUlUNC1ONlE3RTUtNkZLWTRFLThMWTFLMSI7YjoxO3M6Mjc6IllCSUVNUy1LSDNPUFUtVlFSUVJHLVlSOFY0RiI7YjoxO30="));
//print_r($A);
//die();

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/varlog.squid.progress";
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}


function var_log_squid(){
	$GLOBALS["TITLENAME"]="Squid-cache logs location";
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
		build_progress("{failed}",110);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	
	$OrgPath="/var/log/squid";
	$OrgDir=$OrgPath;
	$VarLogSquidLocation=$sock->GET_INFO("VarLogSquidLocation");
	if(trim($VarLogSquidLocation)==null){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No destination defined\n";
		build_progress("{checking}",110);
		return;
	}
	
	if(is_link($OrgDir)){$OrgDir=readlink($OrgDir);}
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Original directory `$OrgDir`\n";
	
	build_progress("{checking}",10);
	
	if($OrgDir==$VarLogSquidLocation){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already defined...\n";
		build_progress("{done}",110);
		return;
	}
	
	build_progress("{creating_directory}",110);
	
	@mkdir($VarLogSquidLocation,0755,true);
	
	
	if(!is_dir($VarLogSquidLocation)){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $VarLogSquidLocation permission denied...\n";
		build_progress("{failed}",110);
		return;
	}
	
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	$chown=$unix->find_program("chown");
	$t=time();
	@touch("$VarLogSquidLocation/$t");
	if(!is_file("$VarLogSquidLocation/$t")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $VarLogSquidLocation permission denied...\n";
		build_progress("{failed}",110);
		return;
	}
	@unlink("$VarLogSquidLocation/$t");
	
	build_progress("{apply_permissions}",15);
	@chmod($VarLogSquidLocation,0755);
	@chown($VarLogSquidLocation, "squid");
	@chgrp($VarLogSquidLocation, "squid");
	build_progress("Copy sources files",20);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Copy $OrgDir to $VarLogSquidLocation\n";
	system("$cp -rp $OrgDir/* $VarLogSquidLocation/");
	build_progress("Remove sources files",30);
	system("$rm -rf $OrgDir");
	build_progress("{linking} $OrgPath",40);
	system("$ln -sf $VarLogSquidLocation $OrgPath");
	
	build_progress("{checking} $OrgPath",45);
	if(!is_link($OrgPath)){
		build_progress("{linking} $OrgPath {failed}",110);
		@mkdir($OrgPath,0755,true);
		@chmod($OrgPath,0755);
		@chown($OrgPath, "squid");
		@chgrp($OrgPath, "squid");
		return;
	}
	
	$NewLink=@readlink($OrgPath);
	echo "New Link $NewLink\n";
	if($NewLink<>$VarLogSquidLocation){
		echo "No match $VarLogSquidLocation\n";
		build_progress("{linking} $OrgPath {failed}",110);
		
	}
	
	
	system("$chown -h squid:squid $OrgPath");
	build_progress("Restarting service",80);
	system("/etc/init.d/squid restart");
	build_progress("{done}",100);
}
