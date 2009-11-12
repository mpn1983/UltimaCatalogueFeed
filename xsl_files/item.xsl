<?xml version="1.0" encoding="UTF-8"?>
<!--
    Document   : basic.xsl
    Created on : 29 September 2009, 10:36
    Author     : Matt Newman
    Description:
        Purpose of transformation follows:

        Transform ultima displays top categories to semantic html
-->
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    version="1.0"
    xmlns:str="http://exslt.org/strings"
    extension-element-prefixes="str">
    <xsl:output method="html"/>

    <xsl:variable name="lowercase" select="'abcdefghijklmnopqrstuvwxyz'" />
    <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

    <xsl:template match="/">
        <div id="{ssxml/ssxmlquery/@name}">
            <xsl:apply-templates select="ssxml/ssxmlquery/categories" />
        </div>
    </xsl:template>

    <xsl:template match="categories">
        <div id="categories">
            <xsl:apply-templates select="category">
                <xsl:sort select="@id" data-type="number" order="ascending" />
            </xsl:apply-templates>
        </div>
    </xsl:template>

    <xsl:template match="category">
        <div id="{str:replace(translate(name, $uppercase, $lowercase), ' ', '-')}" class="category">
            <h3><xsl:value-of select="name" /></h3>
        </div>
    </xsl:template>

</xsl:stylesheet>
