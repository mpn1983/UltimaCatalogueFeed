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

    <xsl:variable name="basePath" select="substring-after(ssxml/ssxmlquery/category/fullfoldername, 'products/')" />
    <xsl:variable name="lowercase" select="'abcdefghijklmnopqrstuvwxyz'" />
    <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

    <xsl:template match="/">
        <div id="category">
            <xsl:apply-templates select="ssxml/ssxmlquery/category" mode="main" />
        </div>
    </xsl:template>

    <xsl:template match="category" mode="main">
        <div class="category">
            <div id="{str:replace(translate(name, $uppercase, $lowercase), ' ', '_')}" class="category">
                <h1>
                    <xsl:value-of select="name" />
                </h1>

                <xsl:variable name="description">
                    <xsl:call-template name="highlightFirstSentance">
                        <xsl:with-param name="text" select="description" />
                    </xsl:call-template>
                </xsl:variable>

                <div class="description">
                    <xsl:value-of select="$description" disable-output-escaping="yes" />
                </div>
                
                <xsl:call-template name="getImages" />
                
            </div>

            <div id="items">
                <h2>Items</h2>
                <xsl:apply-templates select="items/item">
                    <xsl:sort select="@id" data-type="number" order="ascending" />
                </xsl:apply-templates>
            </div>

            <div id="sub-categories">
                <h2>Sub Categories</h2>
                <xsl:apply-templates select="subcategories/category" mode="subcategory">
                    <xsl:sort select="@id" data-type="number" order="ascending" />
                </xsl:apply-templates>
            </div>

        </div>
    </xsl:template>

    <xsl:template match="item">

        <xsl:variable name="itemID" select="str:replace(translate(title, $uppercase, $lowercase), ' ', '_')" />

        <div class="item" id="{$itemID}">
            <h3>
                <xsl:value-of select="title" />
            </h3>

            <!-- commented out awaiting decision on whether a description should be shown on category page
            <xsl:variable name="description">
                <xsl:choose>
                    <xsl:when test="attributes/attribute[@name='Description']">
                        <xsl:call-template name="stripHtml">
                            <xsl:with-param name="text" select="attributes/attribute[@name='Description']" />
                        </xsl:call-template>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:call-template name="stripHtml">
                            <xsl:with-param name="text" select="attributes/attribute[@name='Features']" />
                        </xsl:call-template>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:variable>

            <div class="description">
                <xsl:value-of select="$description" disable-output-escaping="yes" />
            </div>
            -->

            <xsl:call-template name="getThumbnailImages" />

            <div class="links">
                <a href="/html_files/{concat($basePath, $itemID, '.html')}">View <xsl:value-of select="title" /> details</a>
            </div>
            
        </div>
    </xsl:template>

    <xsl:template match="category" mode="subcategory">

        <xsl:variable name="catID" select="str:replace(translate(name, $uppercase, $lowercase), ' ', '-')" />

        <div class="category" id="{$catID}">
            <h3>
                <xsl:value-of select="name" />
            </h3>
        </div>
    </xsl:template>

    <xsl:template name="getImages">
        <div class="images">
            <xsl:for-each select="./*[contains(name(), 'image')]">
                <xsl:for-each select="str:split(@href, '/')">
                    <xsl:if test="contains(., '.')">
                        <div class="image_{position()}">
                            <img src="/images/{.}" alt="" />
                        </div>
                    </xsl:if>
                </xsl:for-each>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template name="getThumbnailImages">
        <div class="thumnailImages">
            <xsl:for-each select="./*[contains(name(), 'image')]">
                <xsl:for-each select="str:split(@href, '/')">
                    <xsl:if test="contains(., '.')">
                        <div class="image_{position()}">
                            <img src="/images/thumbs/{.}" alt="" />
                        </div>
                    </xsl:if>
                </xsl:for-each>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template name="highlightFirstSentance">
        <xsl:param name="text" />

        <xsl:variable name="cleanedText">
            <xsl:call-template name="stripHtml">
                <xsl:with-param name="text" select="$text" />
            </xsl:call-template>
        </xsl:variable>

        <xsl:value-of select="concat('&lt;strong&gt;', substring-before( $cleanedText, '.' ), '&lt;/strong&gt;.', substring-after( $cleanedText, '.' ) )" />
    </xsl:template>

    <xsl:template name="stripHtml">
        <xsl:param name="text"/>

        <xsl:choose>
            <xsl:when test="contains($text, '&gt;')">
                <xsl:choose>
                    <xsl:when test="contains($text, '&lt;')">
                        <xsl:value-of select="substring-before($text, '&lt;')"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="substring-before($text, '&gt;')"/>
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:call-template name="stripHtml">
                    <xsl:with-param name="text" select="substring-after($text, '&gt;')"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$text"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

</xsl:stylesheet>
