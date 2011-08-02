#!/usr/bin/perl
# Convert "metahandle.tab" into .c and .h files.
# Copyright (C) 2007 Hewlett-Packard Development Company, L.P.
#  
#  This program is free software; you can redistribute it and/or
#  modify it under the terms of the GNU General Public License
#  version 2 as published by the Free Software Foundation.
#  
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#  
#  You should have received a copy of the GNU General Public License along
#  with this program; if not, write to the Free Software Foundation, Inc.,
#  51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

open(SRC,"<metahandle.tab") || die "Unable to open metahandle.tab";
open(HDR,">metahandle.h") || die "Unable to open metahandle.h";
open(CODE,">metahandle.c") || die "Unable to open metahandle.c";

print HDR "/* DO NOT EDIT */\n";
print HDR "/* This is auto-generated by metahandle.pl and metahandle.tab */\n";
print CODE "/* DO NOT EDIT */\n";
print CODE "/* This is auto-generated by metahandle.pl and metahandle.tab */\n";
print CODE "#include \"ununpack.h\"\n";
print CODE "#include \"metahandle.h\"\n";

my $Out=CODE;
my $Line;
my @HeaderName=();
my $HeaderType="";
my @Values=();
my $Field,$Value;
my $Linenumber=0;

######################################################
# DisplayC(): Show all values.
######################################################
sub DisplayC
{
  my $i;
  my $Last=shift;
  print CODE "    { ";
  for($i=0; $HeaderName[$i]; $i++)
    {
    if ($i != 0) { print CODE ","; }
    if ($Values[$i] ne "") { print CODE "@Values[$i]"; }
    }
  if (!$Last) { print CODE " },\n"; }
  else { print CODE "    }\n"; }
} # DisplayC()

######################################################
# LoadC(): Given a value, set the right field.
######################################################
sub LoadC
{
  my $Field=shift;
  my $Value=shift;
  my $i;

  # Idiot check the value
  if (($Value =~ /"/) && !($Value =~ /".*"/))
    {
    print STDERR "ERROR: Missing quote on line $Linenumber\n";
    exit(-1);
    }

  # Save the value
  for($i=0; $HeaderName[$i]; $i++)
    {
    if ($HeaderName[$i] eq "$Field")
      {
      $Values[$i] = $Value;
      return;
      }
    }
  print STDERR "ERROR: No header named '$Field' at line $Linenumber\n";
  exit(-1);
} # LoadC()

######################################################
######################################################
while(<SRC>)
  {
  chomp;
  s/\#.*//;  # remove comments
  s/^[[:space:]]*//;
  s/[[:space:]]*$//;
  $Line=$_;
  $Linenumber++;

  if ($Line ne "")
    {
    ($Field,$Value) = split(':',$Line);
    if ($Field =~ /[[:space:]]/)
      {
      $Field = "";
      $Value = "";
      }
    if ($Line !~ /[a-zA-Z0-9]+:/)
      {
      $Field = "";
      $Value = "";
      }

    $Field =~ s/^[[:space:]]*//;
    $Value =~ s/^[[:space:]]*//;
    $Field =~ s/[[:space:]]*$//;
    $Value =~ s/[[:space:]]*$//;
    if ($Line eq "HEADER") { $Out = HDR; }
    elsif ($Line eq "ENDHEADER")
      {
      $Out = CODE; $Line="";
      print CODE "$HeaderType CMD[] =\n  {\n";
      }
    elsif ($Field =~ "HEADERTYPE") { $HeaderType = $Value; }
    elsif ($Field =~ "HEADER")
      {
      $Line =~ s/^HEADER:[[:space:]]*//;
      ($Field,$Value) = split(':',$Line);
      # print "  HEADER: $Value $Field;\n";
      print HDR "  $Value $Field;\n";
      push(@HeaderName,$Field);
      }
    elsif ($Line eq "END")
      {
      DisplayC();
      @Values=();
      }
    elsif ($Field ne "")
      {
      LoadC($Field,$Value);
      }
    else { print $Out "$Line\n"; }
    } # if there is data
  }

print CODE "  };\n";
print HDR "extern cmdlist CMD[];\n";
close(SRC);
close(HDR);
close(CODE);
