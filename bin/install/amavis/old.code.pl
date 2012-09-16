use Net::LDAP;

sub IsLocalDomain($$){
my ($tofind,$ldap)=@_;	
$tofind=lc ($tofind);

my  $mesg = $ldap->search(
    base   => "dc=organizations,$suffix",
    scope  => 'sub',
    filter => "(&(objectclass=domainRelatedObject)(associatedDomain=$tofind))",
     attrs  =>['associatedDomain']
  );
 $mesg->code && die $mesg->error;
 
  foreach my $entry ($mesg->all_entries) {
	foreach my $attr ($entry->attributes) {
		foreach my $value ($entry->get_value($attr)) {
			if (lc ($value)  eq $tofind){
			        do_log(1, "artica-plugin: IsLocalDomain: (&(objectclass=domainRelatedObject)(associatedDomain=%s))=%s",$value,$tofind);
				return 1;
			}
		}
}	}
return IsTargetedDomain($tofind,$ldap);
}
# -----------------------------------------------------------------------------------------------------------------
sub IsTargetedDomain($$){
my ($tofind,$ldap)=@_;	
$tofind=lc ($tofind);

my  $mesg = $ldap->search(
    base   => "dc=organizations,$suffix",
    scope  => 'sub',
    filter => "(&(objectclass=transportTable)(cn=$tofind))",
     attrs  =>['cn']
  );
 $mesg->code && die $mesg->error;
 
  foreach my $entry ($mesg->all_entries) {
	foreach my $attr ($entry->attributes) {
		foreach my $value ($entry->get_value($attr)) {
			if (lc ($value)  eq $tofind){
     			        do_log(1, "artica-plugin: IsTargetedDomain: (&(objectclass=transportTable)(cn=%s))=%s",$value,$tofind); 	
				return 1;
			}
		}
}	}
return 0;}
# -----------------------------------------------------------------------------------------------------------------
# -----------------------------------------------------------------------------------------------------------------
sub ParseAttachParts($$){
  my($msginfo)=@_;
  my($tempdir) = $msginfo->mail_tempdir;  # working directory for this process
  my $part_path;
  my $size;
  my $ext;
  my($parts_root)=$msginfo->parts_root;
  my($top) = $parts_root->children;
  my($tempdir)=$msginfo->mail_tempdir;
  
  
    for my $e (!defined $top ? () : @$top) {
      my($name) = $e->name_declared;
      my($tshort)=$e->type_short;
      my($base_name)=$e->base_name;
      my($type_long)=$e->type_long;
      my($top2) = $e->children;
      for my $b (!defined $top2 ? () : @$top2) {
	  my($name) = $b->name_declared;
      	  my($tshort)=$b->type_short;
      	  my($base_name)=$b->base_name;
      	  my($type_long)=$b->type_long;
	 if(length($name)>0){
	        $name=~ m/\.(.+?)$/;
	        $ext=$1;
		$part_path="$tempdir/parts/$base_name";
 		my $statF=stat($part_path);
		if($statF){
	        	$size=$statF->size;
			if(-e $part_path){
				push(@attachments, "$ext;$size");
			}
		   
		}
	    }
         }
     }

return @attachments;
}

# -----------------------------------------------------------------------------------------------------------------
# -----------------------------------------------------------------------------------------------------------------
sub htmlSize($$){
  my($msginfo,$ldap)=@_;
  my $uid;
  my $ou;
  my @res;
  my $count_attach;
  my($sender_address)=$msginfo->sender;
  my $returned_value;
  
  @attachments=ParseAttachParts($msginfo);
  $count_attach=scalar(@attachments);
  do_log(1, "%s artica-plugin: htmlSize %s attachments...",$message_id,$count_attach);
  if($count_attach==0){return 0;}

 for my $r (@{$msginfo->per_recip_data}) {
	my($recip) = $r->recip_addr;
	$uid=GetUidFromMail($recip,$ldap);
	if(length($uid)>0){
		$search_dn=~ m/cn=$uid,ou=(.+?),/;
		my $ou=$1;
		@res=GetLdapEntries("cn=html_blocker,ou=klf,$suffix","BigMailHTMLEnabled",$ldap);
		@rules=GetLdapEntries("cn=html_blocker,ou=klf,$suffix","BigMailHtmlRules",$ldap);
		if(scalar(@res)>0){
			if($res[0] eq "yes"){
			  	if(scalar(@rules)>0){
					do_log(1, "%s artica-plugin: htmlSize (parse rules)...",$message_id);
					$returned_value=ParseHtmlRules($sender_address,$recip);
					if($returned_value==1){return 1;}
			  	}
			}
		}
	 }
    }
return 0;
}
sub UserDatas($$$){
my ($tofind,$email,$ldap)=@_;
my  $mesg = $ldap->search(
    base   => "dc=organizations,$suffix",
    scope  => 'sub',
    filter => "(&(objectClass=userAccount)(mail=$email))"
  );

if (! defined $mesg) {
	do_log(0, "%s artica-plugin: UserDatas:: LDAP connection problem - temporary error -",$message_id);
	return "";
	}
  if ( $mesg->code ) {
    do_log(0, "%s artica-plugin: UserDatas:: Search failed with error %s",$message_id,$mesg->error);
  }

  foreach my $entry ($mesg->all_entries) {
    $search_dn=$entry->dn;
    foreach my $attr ($entry->attributes) {
        foreach my $value ($entry->get_value($attr)) {
		if($attr eq $tofind){
			 #print "$attr: $value\n";
			 return $value;
		}
	}
    }	
  } 
}

sub GetLdapEntries(){
my ($dn,$field,$ldap)=@_;
my @res;
my  $mesg = $ldap->search(
    base   => $dn,
    scope  => 'sub',
    filter => "(objectClass=*)"
  );
if(defined($mesg)){
	if(defined($mesg->code)){
		if(defined($mesg->error)){
			$mesg->code && die $mesg->error;
		}
	}
}

  foreach my $entry ($mesg->all_entries) {
    foreach my $attr ($entry->attributes) {
        foreach my $value ($entry->get_value($attr)) {
	    if($attr eq $field){
		push(@res, $value);
	    }
	}
    }	
  }

return @res;
}
# -----------------------------------------------------------------------------------------------------------------



sub GetUidFromMail($$){
	my ($email,$ldap)=@_;
	my $uid;
	my $tmpuid=0;
	$uid=UserDatas("uid",$email,$ldap);
	if(length($uid)>0){return $uid;}
	my  $mesg = $ldap->search(
    		base   => "dc=organizations,$suffix",
    		scope  => 'sub',
    		filter => "(&(objectClass=userAccount)(mailAlias=$email))"
  		);	
 $mesg->code && die $mesg->error;
  foreach my $entry ($mesg->all_entries) {
    $search_dn=$entry->dn;
    foreach my $attr ($entry->attributes) {
        foreach my $value ($entry->get_value($attr)) {
	    if($attr eq "mailAlias"){if($value eq $email){$tmpuid=1;}}
	}
    }	
  }

if($tmpuid==1){
	foreach my $entry ($mesg->all_entries) {
    		foreach my $attr ($entry->attributes) {
        		foreach my $value ($entry->get_value($attr)) {
			   if($attr eq "uid"){return $value;}
			}
		}
	}
 }


}
# -----------------------------------------------------------------------------------------------------------------
# -----------------------------------------------------------------------------------------------------------------
sub init_ldap_settings(){
	my $fileset;
	my $admin;
	$fileset="/etc/artica-postfix/artica-postfix-ldap.conf";
	if(!$ldap_server){$ldap_server =GET_INFOS_LDAP('server');}
	if(!$ldap_server_port){ $ldap_server_port=GET_INFOS_LDAP('port');}
	if(!$suffix){$suffix=GET_INFOS_LDAP('suffix');}
	if(!$admin){$admin=GET_INFOS_LDAP('admin');}
	if(!$ldap_password){$ldap_password=GET_INFOS_LDAP('password');}		
	if(!$ldap_admin){$ldap_admin="cn=$admin,$suffix";}
	if(!$ldap_server){$ldap_server="127.0.0.1";}
	if($ldap_server==''){$ldap_server="127.0.0.1";}
	my $ldap = Net::LDAP->new($ldap_server , $ldap_server_port => 389, version => 3)  or die "unable to connect to $ldap_server";
	my $results=$ldap->bind ( $ldap_admin, password => $ldap_password);
	$results->code && die $results->error;		
	return $ldap;
	}
# -----------------------------------------------------------------------------------------------------------------
