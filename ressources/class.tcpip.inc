<?php
/*
 * @Author "Ashar Voultoiz" <hashar@altern.org>
 * @License GPL v2 or later
 */

if(!defined("RE_IP_BYTE")){define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])');}
if(!defined("RE_IP_ADD")){define( 'RE_IP_ADD' , RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );}
if(!defined("RE_IP_PREFIX")){define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)');}
if(!defined("RE_IP_BLOCK")){define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX);}
if(!defined("RE_IPV6_WORD")){define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );}
if(!defined("RE_IPV6_GAP")){define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );}
if(!defined("RE_IPV6_V4_PREFIX")){define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );}
if(!defined("RE_IPV6_PREFIX")){define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)');}
if(!defined("RE_IPV6_ADD")){define( 'RE_IPV6_ADD', '(:(:' . RE_IPV6_WORD . '){1,7}|' . RE_IPV6_WORD . '(:{1,2}' . RE_IPV6_WORD . '|::$){1,7})' );}
if(!defined("RE_IPV6_BLOCK")){define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );}
if(!defined("IP_ADDRESS_STRING")){define( 'IP_ADDRESS_STRING','(?:' .RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)' .'|' .RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)' .')' );}

/**
 * A collection of public static functions to play with IP address
 * and IP blocks.
 */
class IP {
	/**
	 * Given a string, determine if it as valid IP
	 * Unlike isValid(), this looks for networks too
	 * @param $ip IP address.
	 * @return string
	 */
	
	
	public static function isIPAddressOrRange( $ip ) {	
		if(self::isIPAddress($ip)){return true;}
		$array=self::parseRange($ip);
		if(!is_array($array)){return false;}
		if(($array[0]==false) && ($array[1]==false)){return false;}
		
		
	}
	
	public static function isIPAddress( $ip ) {
		$ip=trim($ip);
		if($ip==null){return false;}
		if ( !$ip ) return false;
		if ( is_array( $ip ) ) {return false;}
		if(preg_match("#(.+?)\/([0-9]+)$#", $ip,$re)){
			$re[2]=trim($re[2]);
			if(strlen($re[2])>2){return false;}
			$ip=$re[1];
		}
		if(preg_match("#^".RE_IP_ADD."$#", $ip,$re)){return true;}
		if(preg_match("#^".RE_IPV6_ADD."$#", $ip,$re)){return true;}
		if(substr_count($ip, '::')>0){return false;}
		return false;
	}
	
	public static function isIPv6( $ip ) {
		if ( !$ip ) return false;
		if( is_array( $ip ) ) {
		  throw new MWException( "invalid value passed to " . __METHOD__ );
		}
		// IPv6 IPs with two "::" strings are ambiguous and thus invalid
		return preg_match( '/^' . RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)$/', $ip) && ( substr_count($ip, '::') < 2);
	}
	
	public static function isIPv4( $ip ) {
		if ( !$ip ) return false;
		return preg_match( '/^' . RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)$/', $ip);
	}
	
	
public static function IsvalidMAC($MAC){
        	$MAC=str_replace(":", "-", $MAC);
			If(strlen($MAC) > 17) return false;
			If($MAC == "") return false;
			If (!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$MAC)) return false;
			$Array=explode("-",$MAC);
			If(strlen($Array[0]) != 2) return false;
			If(strlen($Array[1]) != 2) return false;
			If(strlen($Array[2]) != 2) return false;
			If(strlen($Array[3]) != 2) return false;
			If(strlen($Array[4]) != 2) return false;
			If(strlen($Array[5]) != 2) return false;
			return true;
    }
    
public static function ExtractPort($string=null){
	$string=trim($string);
	if($string==null){return false;}
	if(strpos(" $string", ":")==0){return false;}
	$extr=explode(":",$string);
	$port=intval($extr[1]);
	if($port==0){return false;}	
	return $port;
	
}
	
	
	/**
	 * Given an IP address in dotted-quad notation, returns an IPv6 octet.
	 * See http://www.answers.com/topic/ipv4-compatible-address
	 * IPs with the first 92 bits as zeros are reserved from IPv6
	 * @param $ip quad-dotted IP address.
	 * @return string 
	 */
	public static function IPv4toIPv6( $ip ) {
		if ( !$ip ) return null;
		// Convert only if needed
		if ( self::isIPv6( $ip ) ) return $ip;
		// IPv4 CIDRs
		if ( strpos( $ip, '/' ) !== false ) {
			$parts = explode( '/', $ip, 2 );
			if ( count( $parts ) != 2 ) {
				return false;
			}
			$network = self::toUnsigned( $parts[0] );
			if ( $network !== false && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 32 ) {
				$bits = $parts[1] + 96;
				return self::toOctet( $network ) . "/$bits";
			} else {
				return false;
			}
		}
		return self::toOctet( self::toUnsigned( $ip ) );
	}
	
	/**
	 * A second way to transform v4 to 6.
	 * @param $ip octet ipv4 IP address.
	 * @return string
	 */	
	
public static function IPv4To6($ip) {
 if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === true) {
  if (strpos($ip, '.') > 0) {
   $ip = substr($ip, strrpos($ip, ':')+1);
  } else { //native ipv6
   return $ip;
  }
 }
 $is_v4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
 if (!$is_v4) { return false; }
 $iparr = array_pad(explode('.', $ip), 4, 0);
    $Part7 = base_convert(($iparr[0] * 256) + $iparr[1], 10, 16);
    $Part8 = base_convert(($iparr[2] * 256) + $iparr[3], 10, 16);
    return '::ffff:'.$Part7.':'.$Part8;
}	

	/**
	 * Given an IPv6 address in octet notation, returns an unsigned integer.
	 * @param $ip octet ipv6 IP address.
	 * @return string
	 */
	public static function toUnsigned6( $ip ) {
		if ( !$ip ) return null;
       	$ip = explode(':', self::sanitizeIP( $ip ) );
       	$r_ip = '';
       	foreach ($ip as $v) {
       		$r_ip .= str_pad( $v, 4, 0, STR_PAD_LEFT );
        }
        $r_ip = wfBaseConvert( $r_ip, 16, 10 );
       	return $r_ip;
	}
	
		public function maskTocdir($ip, $mask) {
		  return $ip."/".strlen(preg_replace("/0/", "", decbin(ip2long($mask))));
		} 	
			
	/**
	 * Given an IPv6 address in octet notation, returns the expanded octet.
	 * IPv4 IPs will be trimmed, thats it...
	 * @param $ip octet ipv6 IP address.
	 * @return string 
	 */	
	public static function sanitizeIP( $ip ) {
		if ( !$ip ) return null;
		// Trim and return IPv4 addresses
		if ( self::isIPv4($ip) ) return trim($ip);
		// Only IPv6 addresses can be expanded
		if ( !self::isIPv6($ip) ) return $ip;
		// Remove any whitespaces, convert to upper case
		$ip = strtoupper( trim($ip) );
		// Expand zero abbreviations
		if ( strpos( $ip, '::' ) !== false ) {
    		$ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')) . ':', $ip);
    	}
    	// For IPs that start with "::", correct the final IP so that it starts with '0' and not ':'
    	if ( $ip[0] == ':' ) $ip = "0$ip";
    	// Remove leading zereos from each bloc as needed
    	$ip = preg_replace( '/(^|:)0+' . RE_IPV6_WORD . '/', '$1$2', $ip );
    	return $ip;
	}
	
	/**
	 * Given an unsigned integer, returns an IPv6 address in octet notation
	 * @param $ip integer IP address.
	 * @return string 
	 */
	public static function toOctet( $ip_int ) {
   		// Convert to padded uppercase hex
   		$ip_hex = wfBaseConvert($ip_int, 10, 16, 32, false);
   		// Seperate into 8 octets
   		$ip_oct = substr( $ip_hex, 0, 4 );
   		for ($n=1; $n < 8; $n++) {
   			$ip_oct .= ':' . substr($ip_hex, 4*$n, 4);
   		}
   		// NO leading zeroes
   		$ip_oct = preg_replace( '/(^|:)0+' . RE_IPV6_WORD . '/', '$1$2', $ip_oct );
       	return $ip_oct;
	}

	/**
	 * Convert a network specification in IPv6 CIDR notation to an integer network and a number of bits
	 * @return array(string, int)
	 */
	public static function parseCIDR6( $range ) {
		# Expand any IPv6 IP
		$parts = explode( '/', IP::sanitizeIP( $range ), 2 );
		if ( count( $parts ) != 2 ) {
			return array( false, false );
		}
		$network = self::toUnsigned6( $parts[0] );
		if ( $network !== false && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 128 ) {
			$bits = $parts[1];
			if ( $bits == 0 ) {
				$network = 0;
			} else {
			# Native 32 bit functions WONT work here!!!
			# Convert to a padded binary number
				$network = wfBaseConvert( $network, 10, 2, 128 );
			# Truncate the last (128-$bits) bits and replace them with zeros
				$network = str_pad( substr( $network, 0, $bits ), 128, 0, STR_PAD_RIGHT );
			# Convert back to an integer
				$network = wfBaseConvert( $network, 2, 10 );
			}
		} else {
			$network = false;
			$bits = false;
		}
		return array( $network, $bits );
	}
	
	/**
	 * Given a string range in a number of formats, return the start and end of 
	 * the range in hexadecimal. For IPv6.
	 *
	 * Formats are:
	 *     2001:0db8:85a3::7344/96          			 CIDR
	 *     2001:0db8:85a3::7344 - 2001:0db8:85a3::7344   Explicit range
	 *     2001:0db8:85a3::7344/96             			 Single IP
	 * @return array(string, int)
	 */
	public static function parseRange6( $range ) {
		# Expand any IPv6 IP
		$range = IP::sanitizeIP( $range );
		if ( strpos( $range, '/' ) !== false ) {
			# CIDR
			list( $network, $bits ) = self::parseCIDR6( $range );
			if ( $network === false ) {
				$start = $end = false;
			} else {
				$start = wfBaseConvert( $network, 10, 16, 32, false );
				# Turn network to binary (again)
				$end = wfBaseConvert( $network, 10, 2, 128 );
				# Truncate the last (128-$bits) bits and replace them with ones
				$end = str_pad( substr( $end, 0, $bits ), 128, 1, STR_PAD_RIGHT );
				# Convert to hex
				$end = wfBaseConvert( $end, 2, 16, 32, false );
				# see toHex() comment
				$start = "v6-$start"; $end = "v6-$end";
			}
		} elseif ( strpos( $range, '-' ) !== false ) {
			# Explicit range
			list( $start, $end ) = array_map( 'trim', explode( '-', $range, 2 ) );
			$start = self::toUnsigned6( $start ); $end = self::toUnsigned6( $end );
			if ( $start > $end ) {
				$start = $end = false;
			} else {
				$start = wfBaseConvert( $start, 10, 16, 32, false );
				$end = wfBaseConvert( $end, 10, 16, 32, false );
			}
			# see toHex() comment
			$start = "v6-$start"; $end = "v6-$end";
		} else {
			# Single IP
			$start = $end = self::toHex( $range );
		}
		if ( $start === false || $end === false ) {
			return array( false, false );
		} else {
			return array( $start, $end );
		}
    }
    
public static function ipv4ToIpv6Cdir( $original_ip_str ) {  
	# Converto to binary form (suppress errors, we handle them)
	$original_ip_bin = @inet_pton($original_ip_str);
	if ($original_ip_bin === FALSE) {
	  $subnet_str = FALSE;
	  $subnet_bin = FALSE;
	} else {
	  if (strlen($original_ip_bin) == 16) {
	    # IPv6: Replace the last 64 bits with zeroes
	    $subnet_bin = substr_replace($original_ip_bin, str_repeat("\000", 8), -8);
	  }
	
	  # Convert the result back to readable form (optional)
	  $subnet_str = inet_ntop($subnet_bin);
	}
	return 	$original_ip_str."/$subnet_str";
}
   
	
	/**
	 * Validate an IP address.
	 * @return boolean True if it is valid.
	 */
	public static function isValid( $ip ) {
		if($ip==null){return false;}
		return ( preg_match( '#^' . RE_IP_ADD . '$#', $ip) || preg_match( '#^' . RE_IPV6_ADD . '$#', $ip) );
	}
	
	
	public static function IsARange( $ip ) {
		if(preg_match("#^([0-9\.]+)-([0-9\.]+)$#", trim($ip))){return true;}
		return false;
	}
	public static function IsACDIR( $ip ) {
		if(preg_match("#^([0-9\.]+)\/([0-9])([0-9])$#", trim($ip))){return true;}
		return false;
	}	
	

	/**
	 * Validate an IP Block.
	 * @return boolean True if it is valid.
	 */
	public static function isValidBlock( $ipblock ) {
		return ( count(self::toArray($ipblock)) == 1 + 5 );
	}

	/**
	 * Determine if an IP address really is an IP address, and if it is public,
	 * i.e. not RFC 1918 or similar
	 * Comes from ProxyTools.php
	 */
	public static function isPublic( $ip ) {
		$n = self::toUnsigned( $ip );
		if ( !$n ) {
			return false;
		}

		// ip2long accepts incomplete addresses, as well as some addresses
		// followed by garbage characters. Check that it's really valid.
		if( $ip != long2ip( $n ) ) {
			return false;
		}

		static $privateRanges = false;
		if ( !$privateRanges ) {
			$privateRanges = array(
				array( '10.0.0.0',    '10.255.255.255' ),   # RFC 1918 (private)
				array( '172.16.0.0',  '172.31.255.255' ),   #     "
				array( '192.168.0.0', '192.168.255.255' ),  #     "
				array( '0.0.0.0',     '0.255.255.255' ),    # this network
				array( '127.0.0.0',   '127.255.255.255' ),  # loopback
			);
		}

		foreach ( $privateRanges as $r ) {
			$start = self::toUnsigned( $r[0] );
			$end = self::toUnsigned( $r[1] );
			if ( $n >= $start && $n <= $end ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Split out an IP block as an array of 4 bytes and a mask,
	 * return false if it can't be determined
	 *
	 * @param $ip string A quad dotted/octet IP address
	 * @return array
	 */
	public static function toArray( $ipblock ) {
		$matches = array();
		if( preg_match( '/^' . RE_IP_ADD . '(?:\/(?:'.RE_IP_PREFIX.'))?' . '$/', $ipblock, $matches ) ) {
			return $matches;
		} else if ( preg_match( '/^' . RE_IPV6_ADD . '(?:\/(?:'.RE_IPV6_PREFIX.'))?' . '$/', $ipblock, $matches ) ) {
			return $matches;
		} else {
			return false;
		}
	}

	/**
	 * Return a zero-padded hexadecimal representation of an IP address.
	 *
	 * Hexadecimal addresses are used because they can easily be extended to
	 * IPv6 support. To separate the ranges, the return value from this 
	 * function for an IPv6 address will be prefixed with "v6-", a non-
	 * hexadecimal string which sorts after the IPv4 addresses.
	 *
	 * @param $ip Quad dotted/octet IP address.
	 * @return hexidecimal
	 */
	public static function toHex( $ip ) {
		$n = self::toUnsigned( $ip );
		if ( $n !== false ) {
			$n = ( self::isIPv6($ip) ) ? "v6-" . wfBaseConvert( $n, 10, 16, 32, false ) : wfBaseConvert( $n, 10, 16, 8, false );
		}
		return $n;
	}

	/**
	 * Given an IP address in dotted-quad/octet notation, returns an unsigned integer.
	 * Like ip2long() except that it actually works and has a consistent error return value.
	 * Comes from ProxyTools.php
	 * @param $ip Quad dotted IP address.
	 * @return integer
	 */
	public static function toUnsigned( $ip ) {
		// Use IPv6 functions if needed
		if ( self::isIPv6( $ip ) ) {
			return self::toUnsigned6( $ip );
		}
		if ( $ip == '255.255.255.255' ) {
			$n = -1;
		} else {
			$n = ip2long( $ip );
			if ( $n == -1 || $n === false ) { # Return value on error depends on PHP version
				$n = false;
			}
		}
		if ( $n < 0 ) {
			$n += pow( 2, 32 );
		}
		return $n;
	}

	/**
	 * Convert a dotted-quad IP to a signed integer
	 * Returns false on failure
	 */
	public static function toSigned( $ip ) {
		if ( $ip == '255.255.255.255' ) {
			$n = -1;
		} else {
			$n = ip2long( $ip );
			if ( $n == -1 ) {
				$n = false;
			}
		}
		return $n;
	}

	/**
	 * Convert a network specification in CIDR notation to an integer network and a number of bits
	 * @return array(string, int)
	 */
	public static function parseCIDR( $range ) {
		$parts = explode( '/', $range, 2 );
		if ( count( $parts ) != 2 ) {
			return array( false, false );
		}
		$network = self::toSigned( $parts[0] );
		if ( $network !== false && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 32 ) {
			$bits = $parts[1];
			if ( $bits == 0 ) {
				$network = 0;
			} else {
				$network &= ~((1 << (32 - $bits)) - 1);
			}
			# Convert to unsigned
			if ( $network < 0 ) {
				$network += pow( 2, 32 );
			}
		} else {
			$network = false;
			$bits = false;
		}
		return array( $network, $bits );
	}

	/**
	 * Given a string range in a number of formats, return the start and end of 
	 * the range in hexadecimal.
	 *
	 * Formats are:
	 *     1.2.3.4/24          CIDR
	 *     1.2.3.4 - 1.2.3.5   Explicit range
	 *     1.2.3.4             Single IP
	 * 
	 *     2001:0db8:85a3::7344/96          			 CIDR
	 *     2001:0db8:85a3::7344 - 2001:0db8:85a3::7344   Explicit range
	 *     2001:0db8:85a3::7344             			 Single IP
	 * @return array(string, int)
	 */
	public static function parseRange( $range ) {
		// Use IPv6 functions if needed
		if ( self::isIPv6( $range ) ) {
			return self::parseRange6( $range );
		}
		if ( strpos( $range, '/' ) !== false ) {
			# CIDR
			list( $network, $bits ) = self::parseCIDR( $range );
			if ( $network === false ) {
				$start = $end = false;
			} else {
				$start = sprintf( '%08X', $network );
				$end = sprintf( '%08X', $network + pow( 2, (32 - $bits) ) - 1 );
			}
		} elseif ( strpos( $range, '-' ) !== false ) {
			# Explicit range
			list( $start, $end ) = array_map( 'trim', explode( '-', $range, 2 ) );
			$start = self::toUnsigned( $start ); $end = self::toUnsigned( $end );
			if ( $start > $end ) {
				$start = $end = false;
			} else {
				$start = sprintf( '%08X', $start );
				$end = sprintf( '%08X', $end );
			}
		} else {
			# Single IP
			$start = $end = self::toHex( $range );
		}
		if ( $start === false || $end === false ) {
			return array( false, false );
		} else {				
			return array( $start, $end );
		}
    }

    /**
     * Determine if a given IPv4/IPv6 address is in a given CIDR network
     * @param $addr The address to check against the given range.
     * @param $range The range to check the given address against.
     * @return bool Whether or not the given address is in the given range.
     */
    public static function isInRange( $addr, $range ) {
    // Convert to IPv6 if needed
        $unsignedIP = self::toHex( $addr );
        list( $start, $end ) = self::parseRange( $range );
        return (($unsignedIP >= $start) && ($unsignedIP <= $end));
    }


   
    
    
    public static function canonicalize( $addr ) {
	if ( self::isValid( $addr ) )
	    return $addr;

	// IPv6 loopback address
	$m = array();
	if ( preg_match( '/^0*' . RE_IPV6_GAP . '1$/', $addr, $m ) )
	    return '127.0.0.1';

	// IPv4-mapped and IPv4-compatible IPv6 addresses
	if ( preg_match( '/^' . RE_IPV6_V4_PREFIX . '(' . RE_IP_ADD . ')$/i', $addr, $m ) )
	    return $m[1];
	if ( preg_match( '/^' . RE_IPV6_V4_PREFIX . RE_IPV6_WORD . ':' . RE_IPV6_WORD . '$/i', $addr, $m ) )
	    return long2ip( ( hexdec( $m[1] ) << 16 ) + hexdec( $m[2] ) );

	return null;  // give up
    }
    
function ip2cidr($ip_start,$ip_end) {
	if(long2ip(ip2long($ip_start))!=$ip_start or long2ip(ip2long($ip_end))!=$ip_end) return NULL;
	$ipl_start=(int)ip2long($ip_start);
	$ipl_end=(int)ip2long($ip_end);
	if($ipl_start>0 && $ipl_end<0) $delta=($ipl_end+4294967296)-$ipl_start;
	else $delta=$ipl_end-$ipl_start;
	$netmask=str_pad(decbin($delta),32,0,STR_PAD_LEFT);
	if(ip2long($ip_start)==0 && substr_count($netmask,"1")==32) return "0.0.0.0/0";
	if($delta<0 or ($delta>0 && $delta%2==0)) return NULL;
	for($mask=0;$mask<32;$mask++) if($netmask[$mask]==1) break;
	if(substr_count($netmask,"0")!=$mask) return NULL;
	return "$ip_start/$mask";
}

function HostsNumber($ip_addr,$subnet_mask = "255.255.255.0"){
   $ip = ip2long($ip_addr);
   $nm = ip2long($subnet_mask);
   $nw = ($ip & $nm);
   $bc = $nw | (~$nm);
   return ($bc - $nw - 1);
  }

}

Class SecondIP{
	
	var $NETWORK;
	var $BROADCAST;
	var $HOSTS;
	var $RANGE_1;
	var $RANGE_2;
	var $CDR;
	
	function SecondIP($ip_addr,$netmask=null){
		if($netmask<>null){
			$array=$this->ParseInfos($ip_addr,$netmask);
			$this->NETWORK=$array["NETWORK"];
			$this->NETWORK=substr($this->NETWORK,0,strlen($this->NETWORK)-1).'0';
			
			$this->BROADCAST=$array["BROADCAST"];
			$this->BROADCAST=substr($this->BROADCAST,0,strlen($this->BROADCAST)-1).'5';
			$this->RANGE_1=$array["RANGE_1"];
			$this->RANGE_2=$array["RANGE_2"];
			$this->HOSTS=$array["HOSTS"];
			$ip=new IP();
			$this->CDR=$ip->ip2cidr($this->RANGE_1,$this->RANGE_2);
		}
		
	}
	
	function ParseInfos($ip_addr,$subnet_mask){
	$ip = ip2long($ip_addr);
  	 $nm = ip2long($subnet_mask);
   	$nw = ($ip & $nm);
   	$bc = $nw | (~$nm);
	return array(
	"NETWORK"=>long2ip($nw),
	"BROADCAST"=> long2ip($bc),
	"HOSTS"=>($bc - $nw - 1),
	"RANGE_1"=> long2ip($nw),
	"RANGE_2"=>  long2ip($bc)
	);
	}
}

function wfBaseConvert( $input, $sourceBase, $destBase, $pad=1, $lowercase=true ) {
	$input = strval( $input );
	if( $sourceBase < 2 ||
		$sourceBase > 36 ||
		$destBase < 2 ||
		$destBase > 36 ||
		$pad < 1 ||
		$sourceBase != intval( $sourceBase ) ||
		$destBase != intval( $destBase ) ||
		$pad != intval( $pad ) ||
		!is_string( $input ) ||
		$input == '' ) {
		return false;
	}
	$digitChars = ( $lowercase ) ?  '0123456789abcdefghijklmnopqrstuvwxyz' : '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$inDigits = array();
	$outChars = '';
	
	// Decode and validate input string
	$input = strtolower( $input );
	for( $i = 0; $i < strlen( $input ); $i++ ) {
		$n = strpos( $digitChars, $input{$i} );
		if( $n === false || $n > $sourceBase ) {
			return false;
		}
		$inDigits[] = $n;
	}
	
	// Iterate over the input, modulo-ing out an output digit
	// at a time until input is gone.
	while( count( $inDigits ) ) {
		$work = 0;
		$workDigits = array();
		
		// Long division...
		foreach( $inDigits as $digit ) {
			$work *= $sourceBase;
			$work += $digit;
			
			if( $work < $destBase ) {
				// Gonna need to pull another digit.
				if( count( $workDigits ) ) {
					// Avoid zero-padding; this lets us find
					// the end of the input very easily when
					// length drops to zero.
					$workDigits[] = 0;
				}
			} else {
				// Finally! Actual division!
				$workDigits[] = intval( $work / $destBase );
				
				// Isn't it annoying that most programming languages
				// don't have a single divide-and-remainder operator,
				// even though the CPU implements it that way?
				$work = $work % $destBase;
			}
		}
		
		// All that division leaves us with a remainder,
		// which is conveniently our next output digit.
		$outChars .= $digitChars[$work];
		
		// And we continue!
		$inDigits = $workDigits;
	}
	
	while( strlen( $outChars ) < $pad ) {
		$outChars .= '0';
	}
	
	return strrev( $outChars );
}

class ipv4
{
  var $address;
  var $netbits;

   //--------------
  // Create new class
  function ipv4($address,$netbits)
  {
    $this->address = $address;
    $this->netbits = $netbits;
  }

   //--------------
  // Return the IP address
  function address() { return ($this->address); }

   //--------------
  // Return the netbits
  function netbits() { return ($this->netbits); }

   //--------------
  // Return the netmask
  function netmask()
  {
    return (long2ip(ip2long("255.255.255.255")
           << (32-$this->netbits)));
  }

   //--------------
  // Return the network that the address sits in
  function network()
  {
    return (long2ip((ip2long($this->address))
           & (ip2long($this->netmask()))));
  }

   //--------------
  // Return the broadcast that the address sits in
  function broadcast()
  {
    return (long2ip(ip2long($this->network())
           | (~(ip2long($this->netmask())))));
  }

   //--------------
  // Return the inverse mask of the netmask
  function inverse()
  {
    return (long2ip(~(ip2long("255.255.255.255")
           << (32-$this->netbits))));
  }

}


?>